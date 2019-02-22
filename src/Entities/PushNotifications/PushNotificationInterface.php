<?php

namespace EMedia\OxygenPushNotifications\Entities\PushNotifications;


interface PushNotificationInterface
{

	/**
	 *
	 * Return push notification data as an array
	 *
	 * @return array
	 */
	public function getPushNotificationData();

}
