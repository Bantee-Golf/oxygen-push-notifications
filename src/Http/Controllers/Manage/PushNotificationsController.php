<?php


namespace EMedia\OxygenPushNotifications\Http\Controllers\Manage;


use App\Entities\PushNotifications\PushNotification;
use App\Entities\PushNotifications\PushNotificationsRepository;
use App\Http\Controllers\Controller;

use EMedia\Oxygen\Http\Controllers\Traits\HasHttpCRUD;
use EMedia\OxygenPushNotifications\Domain\PushNotificationManager;
use EMedia\OxygenPushNotifications\Domain\PushNotificationTopic;
use EMedia\OxygenPushNotifications\Exceptions\UnknownRecepientException;
use Illuminate\Http\Request;

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

	/**
	 * @param Request $request
	 * @param null    $id
	 *
	 * @return mixed
	 * @throws UnknownRecepientException
	 */
	protected function storeOrUpdateRequest(Request $request, $id = null)
	{
		if (empty($this->indexRouteName()))
			throw new \InvalidArgumentException("'indexRouteName()' returns an empty value.");

		$this->validate($request, $this->model->getRules());

		/** @var PushNotification $entity */
		$entity = $this->dataRepo->fillFromRequest($request, $id);

		// send push notification
		if (env('OXYGEN_PUSH_NOTIFICATIONS_SANDBOX', false)) {
			PushNotificationManager::sendStoredPushNotification($entity);
		}

		return redirect()->route($this->indexRouteName());
	}

}
