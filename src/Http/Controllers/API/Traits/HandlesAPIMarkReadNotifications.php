<?php


namespace EMedia\OxygenPushNotifications\Http\Controllers\API\Traits;


use App\Entities\PushNotifications\PushNotification;
use EMedia\Api\Docs\APICall;
use EMedia\Api\Docs\Param;
use Illuminate\Http\Request;

trait HandlesAPIMarkReadNotifications
{

	protected $pushNotificationsRepo;

	/**
	 *
	 * Mark a notification as read
	 *
	 * @param Request $request
	 *
	 * @return \Illuminate\Http\JsonResponse
	 * @throws \Illuminate\Validation\ValidationException
	 */
	public function markRead(Request $request)
	{
		document(function () {
			return (new APICall)
				->setGroup('Notifications')
				->setName('Mark a Notification as Read')
				->noDefaultHeaders()->setHeaders([
					(new Param('Accept', 'String', '`application/json`'))->setDefaultValue('application/json'),
					(new Param('x-api-key', 'String', 'API Key'))->setDefaultValue('123123123123'),
				])
				->setParams([
					(new Param('device_id', 'String', 'Unique ID of the device')),
					(new Param('device_type', 'String', 'Type of the device `apple` or `android`')),
					(new Param('uuid', 'String', 'Notification Uuid - Sent by the server for the notification')),
				])
				->setSuccessExample('{
									"payload": {
										"uuid": "1234-1234-1234-SEED",
										"title": "SENT_TOPIC_IOS_SEED_NOTIFICATION_2",
										"message": "Nostrum velit beatae ad accusamus.",
										"badge_count": null,
										"data": [],
										"is_read": true
									},
									"message": "",
									"results": true
								}')
				->setSuccessObject(PushNotification::class);
		});

		$this->validate($request, [
			'device_id' => 'required',
			'device_type' => 'required',
			'uuid' => 'required',
		]);

		$device = $this->devicesRepo->getByIDAndType($request->device_id, $request->device_type);

		if (!$device) {
			return response()->apiError('Device data does not exist.');
		}

		/** @var PushNotification $notification */
		$notification = $this->pushNotificationsRepo->findByUuid($request->uuid);

		// get the notification
		if (!$notification) {
			return response()->apiError('Invalid UUID');
		}

		// if it's individual/device - mark as read
		if ($notification->isUserOrDeviceNotification()) {
			$this->pushNotificationsRepo->markAsRead($notification);
		} else {
			// if it's a topic - mark as read on the read table
			try {
				$notification->status()->sync([
					$device->id => [
						'read_at' => now()
					]
				]);
			} catch (\Exception $ex) {
				report($ex);
				return response()->apiError($ex->getMessage());
			}

			$notification->read_at = now();
		}

		// return the response
		return response()->apiSuccess($notification);
	}

}