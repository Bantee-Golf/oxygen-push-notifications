<?php


namespace EMedia\OxygenPushNotifications\Http\Controllers\API\Traits;


use EMedia\Api\Docs\APICall;
use EMedia\Api\Docs\Param;
use EMedia\Devices\Entities\Devices\Device;
use EMedia\OxygenPushNotifications\Domain\PushNotificationManager;
use Illuminate\Http\Request;

trait HandlesAPIDeviceTokenSubscriptions
{

	protected $devicesRepo;

	/**
	 *
	 * Subscribe a device to the database
	 *
	 * @param Request $request
	 *
	 * @return \Illuminate\Http\JsonResponse
	 * @throws \Illuminate\Validation\ValidationException
	 */
	public function subscribe(Request $request)
	{
		document(function () {
			return (new APICall)
				->setGroup('Notifications')
				->setName('Subscribe Device')
				->noDefaultHeaders()->setHeaders([
					(new Param('Accept', 'String', '`application/json`'))->setDefaultValue('application/json'),
					(new Param('x-api-key', 'String', 'API Key'))->setDefaultValue('123123123123'),
				])
				->setParams([
					(new Param('device_id', 'String', 'Unique ID of the device')),
					(new Param('device_type', 'String', 'Type of the device `apple` or `android`')),
					(new Param('device_push_token', 'String', 'Unique push token for the device'))->optional(),
				])
				->setSuccessExample('{
					"payload": {
						"device_id": "TEST_1234",
						"device_type": "apple",
						"device_push_token": "PUSH_TEST_1234",
						"access_token": "1557201626ZkDGtOp0kKomSZiXYE14ULz0qZX5gVAmJNC",
						"access_token_expires_at": "2019-08-05 14:00:26",
						"updated_at": "2019-05-07 14:00:26",
						"created_at": "2019-05-07 14:00:26",
						"id": 5
					},
					"message": "",
					"results": true
				}')
				->setSuccessObject(Device::class);
		});


		$this->validate($request, [
			'device_id' => 'required',
			'device_type' => 'required',
			'device_push_token' => 'required',
		]);

		$deviceData = $request->only(['device_id', 'device_type', 'device_push_token']);
		$device = $this->devicesRepo->createOrUpdateByIDAndType($deviceData);

		// subscribe device to topic
		try {
			PushNotificationManager::subscribeDeviceToBroadcastTopics($device, true);
		} catch (\Exception $ex) {
			report($ex);
		}

		return response()->apiSuccess($device);
	}

}