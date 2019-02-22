<?php

namespace App\Entities\PushNotifications;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class PushNotificationsRepository extends \EMedia\OxygenPushNotifications\Entities\PushNotifications\PushNotificationsRepository
{

	protected function fillCustomFields(Request $request, Model $entity)
	{
		$entity->scheduled_at = now();
		$entity->scheduled_timezone = now()->timezoneName;
	}

}
