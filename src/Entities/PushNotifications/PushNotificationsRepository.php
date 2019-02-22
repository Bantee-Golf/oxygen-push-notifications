<?php


namespace EMedia\OxygenPushNotifications\Entities\PushNotifications;


use App\Entities\BaseRepository;
use App\Entities\PushNotifications\PushNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class PushNotificationsRepository extends BaseRepository
{

	public function __construct(PushNotification $model)
	{
		parent::__construct($model);
	}

}
