<?php


namespace EMedia\OxygenPushNotifications\Domain;


use App\Entities\PushNotifications\PushNotification;
use EMedia\Devices\Entities\Devices\Device;
use EMedia\OxygenPushNotifications\Entities\PushNotifications\PushNotificationInterface;
use EMedia\OxygenPushNotifications\Exceptions\UnknownRecepientException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\AndroidConfig;
use Kreait\Firebase\Messaging\ApnsConfig;
use Kreait\Firebase\Messaging\CloudMessage;

class PushNotificationManager
{

	/*
	|--------------------------------------------------------------------------
	| Send Push Notifications to Devices and Users
	|--------------------------------------------------------------------------
	*/

	/**
	 *
	 * Send a Stored Push Notification
	 *
	 * @param PushNotification $pushNotification
	 *
	 * @throws UnknownRecepientException
	 */
	public static function sendStoredPushNotification(PushNotification $pushNotification)
	{
		$notifiable = $pushNotification->notifiable;

		if ($notifiable instanceof \App\User) {
			self::sendPushNotificationToUser($notifiable, $pushNotification, $pushNotification->data, $pushNotification->apnsConfig, $pushNotification->androidConfig);
		} elseif ($notifiable instanceof Device) {
			self::sendPushNotificationToDevice($notifiable, $pushNotification, $pushNotification->data, $pushNotification->apnsConfig, $pushNotification->androidConfig);
		} elseif (!empty($pushNotification->topic)) {
			self::sendPushNotificationToTopic($pushNotification->topic, $pushNotification, $pushNotification->data, $pushNotification->apnsConfig, $pushNotification->androidConfig);
		} else {
			// unknown receiver or topic
			throw new UnknownRecepientException();
		}
	}

	/**
	 *
	 * Send a push notification to a user's all devices
	 *
	 * @param \App\User                 $user
	 * @param PushNotificationInterface $pushNotification
	 */
	public static function sendPushNotificationToUser(\App\User $user, PushNotificationInterface $pushNotification, $extraData = [], $apnsConfig = null, $androidConfig = null)
	{
		$firebase = (new Factory)->create();
		$messaging = $firebase->getMessaging();

		$response = [];

		foreach ($user->devices as $device) {
			if (!$device->device_push_token) continue;

			$message = CloudMessage::withTarget('token', $device->device_push_token);
			$message = self::buildMessage($message, $pushNotification, $extraData, $apnsConfig, $androidConfig);

			$result = $messaging->send($message);

			/*
			|--------------------------------------------------------------------------
			| // TODO: fix the response detection
			|--------------------------------------------------------------------------
			*/


			//
			//			array:1 [▼
			//  "name" => "projects/cummins-south-pacific/messages/1550852395324358"
			//]


			// this will only record the sent time of the last device
			$pushNotification->touchSentTimestamp();

			$response[] = $result;
		}

		return $response;
	}

	/**
	 *
	 * Send Push notification to a specific device
	 *
	 * @param Device                    $device
	 * @param PushNotificationInterface $pushNotification
	 */
	public static function sendPushNotificationToDevice(Device $device, PushNotificationInterface $pushNotification, $extraData = [], $apnsConfig = null, $androidConfig = null)
	{
		$firebase = (new Factory)->create();
		$messaging = $firebase->getMessaging();

		$message = CloudMessage::withTarget('token', $device->device_push_token);
		$message = self::buildMessage($message, $pushNotification, $extraData, $apnsConfig, $androidConfig);

		$isSuccessfulResponse = self::isResponseSuccessful($messaging->send($message));

		//if ($isSuccessfulResponse)
		$pushNotification->touchSentTimestamp();

		return $isSuccessfulResponse;
	}

	/*
	|--------------------------------------------------------------------------
	| Send Push Notifications to Topics
	|--------------------------------------------------------------------------
	*/

	/**
	 *
	 * Send a push notification to a topic by name
	 *
	 * @param                           $topicName
	 * @param PushNotificationInterface $pushNotification
	 */
	public static function sendPushNotificationToTopic($topicName, PushNotificationInterface $pushNotification, $extraData = [], $apnsConfig = null, $androidConfig = null)
	{
		$firebase = (new Factory)->create();
		$messaging = $firebase->getMessaging();

		$message = CloudMessage::withTarget('topic', $topicName);
		$message = self::buildMessage($message, $pushNotification, $extraData, $apnsConfig, $androidConfig);

		$isSuccessfulResponse = self::isResponseSuccessful($messaging->send($message));

		//if ($isSuccessfulResponse)
		$pushNotification->touchSentTimestamp();

		return $isSuccessfulResponse;
	}

	/*
	|--------------------------------------------------------------------------
	| Bulk register devices to Broadcast Topics
	|--------------------------------------------------------------------------
	*/

	/**
	 *
	 * Register all devices to the General broadcast topic
	 *
	 * @throws \Exception
	 */
	public static function registerAllDevicesToGeneralBroadcast()
	{
		$query = \EMedia\Devices\Entities\Devices\Device::query();
		$query->where('is_subscribed_to_all_devices_topic', false);

		return self::registerDevicesToTopic($query, PushNotificationTopic::TOPIC_ALL_DEVICES, 'is_subscribed_to_all_devices_topic');
	}

	/**
	 *
	 * Register all iOS devices to the broadcast topic
	 *
	 * @throws \Exception
	 */
	public static function registerDevicesToIOSBroadcast()
	{
		$query = \EMedia\Devices\Entities\Devices\Device::query();
		$query->where('device_type', 'apple')->where('is_subscribed_to_device_type_topic', false);

		return self::registerDevicesToTopic($query, PushNotificationTopic::TOPIC_IOS_DEVICES, 'is_subscribed_to_device_type_topic');
	}

	/**
	 *
	 * Register all Android devices to the broadcast topic
	 *
	 * @throws \Exception
	 */
	public static function registerDevicesToAndroidBroadcast()
	{
		$query = \EMedia\Devices\Entities\Devices\Device::query();
		$query->where('device_type', 'android')->where('is_subscribed_to_device_type_topic', false);

		return self::registerDevicesToTopic($query, PushNotificationTopic::TOPIC_ANDROID_DEVICES, 'is_subscribed_to_device_type_topic');
	}

	/**
	 *
	 * Register devices by query to a given topic. Returns the registered count.
	 *
	 * @param Builder $query
	 * @param         $topicName
	 *
	 * @return array
	 * @throws \Exception
	 */
	public static function registerDevicesToTopic(Builder $query, $topicName, $touchableColumnName = null): array
	{
		$count = $query->count();
		if ($count > 100000) throw new \Exception("$count records found. Too many to process. Max limit is 100000 devices");

		// process 1000 records at a time - this is the max limit by `subscribeToTopic()`
		$recordsPerIteration = 1000;
		$processedRecordCount = 0;
		$firebase = (new Factory)->create();

		$successCount = $failedCount = 0;

		$query->whereNotNull('device_push_token');
		$query->where('device_push_token', '!=', '');

		while ($processedRecordCount < $count) {
			$successDevices = new Collection();
			$failedDevices  = new Collection();
			$startNumber = $processedRecordCount;

			$devices = $query->limit($recordsPerIteration)
							 ->offset($startNumber)
							 ->get();

			$pushTokens = $devices->pluck('device_push_token')->toArray();

			$response = $firebase->getMessaging()->subscribeToTopic($topicName, $pushTokens);

			for ($i = 0, $iMax = $devices->count(); $i < $iMax; $i++) {
				$result = null;
				try {
					$result = $response['results'][$i];
				} catch (\Exception $ex) {
					// ignore
				}
				$device = $devices[$i];
				if (empty($result)) {
					$successDevices->push($device);
					continue;
				}
				if (isset($result['error'])) {
					$failedDevices->push($device);
				} else {
					$successDevices->push($device);
				}
			}

			if ($touchableColumnName !== null) {
				foreach ($successDevices as $device) {
					$device->$touchableColumnName = true;
					$device->save();
				}
			}

			$successCount += $successDevices->count();
			$failedCount += $failedDevices->count();

			$processedRecordCount += $recordsPerIteration;
		}

		return [
			'success_count' => $successCount,
			'failed_count'  => $failedCount,
		];
	}

	/*
	|--------------------------------------------------------------------------
	| Subscribe/Unsubscribe Devices from Topics
	|--------------------------------------------------------------------------
	*/

	/**
	 *
	 * Subscribe device to broadcast topics
	 *
	 * @param Device $device
	 * @param 		 $force
	 *
	 * @return bool
	 */
	public static function subscribeDeviceToBroadcastTopics(Device $device, $force = false): bool
	{
		$isResponseSuccessful = true;

		// don't go further if there's no token
		if (empty($device->device_push_token)) return false;

		// don't subscribe if already subscribed
		if ($force || !$device->is_subscribed_to_all_devices_topic) {
			$isResponseSuccessful = self::subscribeDeviceToTopic($device, PushNotificationTopic::TOPIC_ALL_DEVICES);

			if ($isResponseSuccessful) {
				$device->is_subscribed_to_all_devices_topic = true;
			}
		}

		// don't subscribe if already subscribed
		if ($force || !$device->is_subscribed_to_device_type_topic) {
			$response2 = null;
			if ($device->device_type === 'apple') {
				$response2 = self::subscribeDeviceToTopic($device, PushNotificationTopic::TOPIC_IOS_DEVICES);
			} elseif ($device->device_type === 'android') {
				$response2 = self::subscribeDeviceToTopic($device, PushNotificationTopic::TOPIC_ANDROID_DEVICES);
			}

			if ($response2) {
				if (self::isResponseSuccessful($response2)) {
					$device->is_subscribed_to_device_type_topic = true;
				} else {
					$isResponseSuccessful = false;
				}
			}
		}

		if ($device->isDirty()) $device->save();

		return $isResponseSuccessful;
	}

	/**
	 *
	 * Subscribe a single device to a topic
	 *
	 * @param Device $device
	 * @param        $topicName
	 *
	 * @return bool
	 */
	public static function subscribeDeviceToTopic(Device $device, $topicName)
	{
		$firebase = (new Factory)->create();

		return self::isResponseSuccessful($firebase->getMessaging()->subscribeToTopic($topicName, [$device->device_push_token]));
	}

	/**
	 *
	 * Subscribe a collection of devices to a topic
	 *
	 * @param Collection $devices
	 * @param            $topicName
	 *
	 * @return array
	 */
	public static function subscribeDevicesToTopic(Collection $devices, $topicName)
	{
		$firebase = (new Factory)->create();

		return $firebase->getMessaging()->subscribeToTopic($topicName, [$devices->pluck('device_push_token')->toArray()]);
	}

	/**
	 *
	 * Unsubscribe a Device from a Topic
	 *
	 * @param Device $device
	 * @param        $topicName
	 *
	 * @return bool
	 */
	public static function unsubscribeDeviceFromTopic(Device $device, $topicName)
	{
		$firebase = (new Factory)->create();

		return self::isResponseSuccessful($firebase->getMessaging()->unsubscribeFromTopic($topicName, [$device->device_push_token]));
	}

	/**
	 *
	 * Unsubscribe a Collection of Devices from a Topic
	 *
	 * @param Collection $devices
	 * @param            $topicName
	 *
	 * @return array
	 */
	public static function unsubscribeDevicesFromTopic(Collection $devices, $topicName)
	{
		$firebase = (new Factory)->create();

		return $firebase->getMessaging()->unsubscribeFromTopic($topicName, [$devices->pluck('device_push_token')->toArray()]);
	}

	/**
	 *
	 * Check if the request is successful
	 *
	 * @param $response
	 *
	 * @return bool
	 */
	protected static function isResponseSuccessful($response)
	{
		if ((is_array($response) &&
				isset($response['results']) &&
				is_array($response['results']) &&
				isset($response['results'][0])) &&
			!isset($response['results'][0]['error'])) {
			return true;
		}

		return false;
	}

	/**
	 *
	 * Build the Cloud message with the config options
	 *
	 * @param CloudMessage              $message
	 * @param PushNotificationInterface $pushNotification
	 * @param                           $extraData
	 * @param                           $apnsConfig
	 * @param                           $androidConfig
	 *
	 * @return CloudMessage
	 */
	protected static function buildMessage(CloudMessage $message, PushNotificationInterface $pushNotification, $extraData, $apnsConfig, $androidConfig)
	{
		$message = $message->withNotification($pushNotification->getCloudNotification());

		// add any extra data fields
		if (method_exists($pushNotification, 'getPushNotificationData')) {
			$notificationData = $pushNotification->getPushNotificationData();
			$extraData = array_merge($extraData, $notificationData);
		};
		if (!empty($extraData)) $message = $message->withData($extraData);

		if ($apnsConfig) $message = $message->withApnsConfig($apnsConfig);
		if ($androidConfig) $message = $message->withAndroidConfig($androidConfig);

		return $message;
	}

}
