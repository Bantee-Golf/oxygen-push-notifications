<?php


namespace EMedia\OxygenPushNotifications\Http\Controllers\Manage;


use App\Entities\PushNotifications\PushNotification;
use App\Entities\PushNotifications\PushNotificationsRepository;
use App\Http\Controllers\Controller;

use EMedia\Oxygen\Http\Controllers\Traits\HasHttpCRUD;

class PushNotificationsController extends Controller
{

	use HasHttpCRUD;

	public function __construct(PushNotificationsRepository $dataRepo, PushNotification $model)
	{
		$this->dataRepo = $dataRepo;
		$this->model = $model;

		$this->entitySingular = 'Push Notification';
		$this->entityPlural   = 'Push Notifications';

		$this->isDestroyingEntityAllowed = true;
	}

	protected function indexRouteName()
	{
		return 'manage.push-notifications.index';
	}

	protected function indexViewName()
	{
		return 'oxygen-push-notifications::manage.index';
	}

}
