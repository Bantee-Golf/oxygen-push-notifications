<?php


namespace EMedia\OxygenPushNotifications\Http\Controllers\Manage;


use EMedia\OxygenPushNotifications\Entities\PushNotifications\PushNotification;
use EMedia\OxygenPushNotifications\Entities\PushNotifications\PushNotificationsRepository;
use App\Http\Controllers\Controller;

use EMedia\Devices\Entities\Devices\Device;
use EMedia\Devices\Entities\Devices\DevicesRepository;
use EMedia\Formation\Builder\Formation;
use ElegantMedia\OxygenFoundation\Http\Traits\Web\CanCRUD;
use EMedia\OxygenPushNotifications\Domain\PushNotificationManager;
use EMedia\OxygenPushNotifications\Domain\PushNotificationTopic;
use EMedia\OxygenPushNotifications\Exceptions\UnknownRecepientException;
use Illuminate\Http\Request;

class PushNotificationsController extends Controller
{

	use CanCRUD;

	public function __construct(PushNotificationsRepository $repo, PushNotification $model)
	{
		$this->dataRepo = $dataRepo;
		$this->model = $model;

		$this->entitySingular = 'Push Notification';
		$this->entityPlural   = 'Push Notifications';

        $this->repo = $repo;

        $this->resourceEntityName = 'Push Notifications';

        $this->viewsVendorName = 'oxygen-push-notifications';

        $this->resourcePrefix = 'manage';

        $this->isDestroyAllowed = true;
	}

	protected function indexRouteName()
	{
		return 'manage.push-notifications.index';
	}

	protected function indexViewName()
	{
		return 'oxygen-push-notifications::manage.index';
	}

	protected function formViewName(): string
	{
		return 'oxygen-push-notifications::manage.form';
	}

	/**
	 *
	 * Create a new record view
	 *
	 * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
	 */
	public function create()
	{
		if (empty($this->getEntitySingular()))
			throw new \InvalidArgumentException("'entitySingular' value of the controller is not set.");

		$device = null;
		if (request()->has('device_id')) {
			$devicesRepo = app(DevicesRepository::class);
			$device = $devicesRepo->find(request()->input('device_id'));
		}

		$data = [
			'pageTitle' => 'Add new ' . $this->getEntitySingular(),
			'entity' => $this->model,
			'form' => new Formation($this->model),
			'device' => $device,
		];

		$viewName = $this->formViewName();

		return view($viewName, $data);
	}

	/**
	 *
	 * Edit the resource
	 *
	 * @param $id
	 *
	 * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
	 */
	public function edit($id)
	{
		if (empty($this->getEntitySingular()))
			throw new \InvalidArgumentException("'entityPlural' value of the controller is not set.");

		$entity = $this->dataRepo->find($id);
		$form = new Formation($entity);

		$device = null;
		if ($entity->notifiable instanceof Device) {
			$device = $entity->notifiable;
		}

		$data = [
			'pageTitle' => 'Edit ' . $this->getEntitySingular(),
			'entity' => $entity,
			'form' => $form,
			'device' => $device,
		];

		$viewName = $this->formViewName();
		if (empty($viewName)) {
			throw new \InvalidArgumentException("'indexViewName' is empty. Override indexViewName() method in controller.");
		}

		return view($viewName, $data);
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
