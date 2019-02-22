<?php


namespace EMedia\OxygenPushNotifications\Domain;


use EMedia\Devices\Entities\Devices\Device;
use EMedia\OxygenPushNotifications\Entities\PushNotifications\PushNotificationInterface;
use Illuminate\Database\Query\Builder;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;

class PushNotificationManager
{

	/**
	 *
	 * Send a push notification to a user's all devices
	 *
	 * @param                           $user
	 * @param PushNotificationInterface $pushNotification
	 */
	public static function sendPushNotificationToUser($user, PushNotificationInterface $pushNotification)
	{
		$firebase = (new Factory)->create();
		$messaging = $firebase->getMessaging();

		foreach ($user->devices as $device) {
			if (!$device->push_token) continue;

			$message = CloudMessage::withTarget('token', $device->push_token)->withData($pushNotification->getPushNotificationData());

			$messaging->send($message);
		}
	}

	/**
	 *
	 * Send Push notification to a specific device
	 *
	 * @param Device                    $device
	 * @param PushNotificationInterface $pushNotification
	 */
	public static function sendPushNotificationToDevice(Device $device, PushNotificationInterface $pushNotification)
	{
		$firebase = (new Factory)->create();
		$messaging = $firebase->getMessaging();

		$message = CloudMessage::withTarget('token', $device->push_token)->withData($pushNotification->getPushNotificationData());

		$messaging->send($message);
	}

	/**
	 *
	 * Send a push notification to a topic by name
	 *
	 * @param                           $topicName
	 * @param PushNotificationInterface $pushNotification
	 */
	public function sendPushNotificationToTopic($topicName, PushNotificationInterface $pushNotification)
	{
		$firebase = (new Factory)->create();
		$messaging = $firebase->getMessaging();

		$message = CloudMessage::withTarget('topic', $topicName)
							   // ->withNotification($notification) // optional
							   ->withData($pushNotification->getPushNotificationData());

		$messaging->send($message);
	}

	/**
	 *
	 * Register all devices to the General broadcast topic
	 *
	 */
	public function registerAllDevicesToGeneralBroadcast()
	{
		$query = \EMedia\Devices\Entities\Devices\Device::query();

		$count = self::registerDevicesToTopic($query, PushNotificationTopic::TOPIC_ALL_DEVICES);
	}

	/**
	 *
	 * Register all iOS devices to the broadcast topic
	 *
	 */
	public static function registerDevicesToiOSBroadcast()
	{
		$query = \EMedia\Devices\Entities\Devices\Device::where('device_type', 'apple')->query();

		$count = self::registerDevicesToTopic($query, PushNotificationTopic::TOPIC_IOS_DEVICES);
	}

	/**
	 *
	 * Register all Android devices to the broadcast topic
	 *
	 */
	public static function registerDevicesToAndroidBroadcast()
	{
		$query = \EMedia\Devices\Entities\Devices\Device::where('device_type', 'android')->query();

		$count = self::registerDevicesToTopic($query, PushNotificationTopic::TOPIC_ANDROID_DEVICES);
	}

	/**
	 *
	 * Register devices by query to a given topic. Returns the registered count.
	 *
	 * @param Builder $query
	 * @param         $topicName
	 *
	 * @return int
	 */
	public static function registerDevicesToTopic(Builder $query, $topicName): int
	{
		$count = $query->count();
		if ($count > 50000) throw \Exception("$count records found. Too many to process.");

		$recordsPerIteration = 1000;
		$processedRecordCount = 0;
		$firebase = (new Factory)->create();

		while ($processedRecordCount < $count) {
			$startNumber = $processedRecordCount;

			$devices = $query->limit($recordsPerIteration)
							 ->offset($startNumber)
							 ->get();

			$pushTokens = $devices->pluck('device_push_token')->toArray();

			$firebase->getMessaging()->subscribeToTopic($topicName, $pushTokens);

			$processedRecordCount += $recordsPerIteration;
		}

		return $count;
	}

}
