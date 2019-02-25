<?php


namespace EMedia\OxygenPushNotifications\Entities\PushNotifications;


use App\Entities\BaseRepository;
use App\Entities\PushNotifications\PushNotification;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class PushNotificationsRepository extends BaseRepository
{

	public function __construct(PushNotification $model)
	{
		parent::__construct($model);
	}

	/**
	 *
	 * Get an unsent push notification by ID
	 *
	 * @param $id
	 *
	 * @return \Illuminate\Database\Eloquent\Builder|Model
	 */
	public function getUnsetPushNotification($id)
	{
		$query = $this->getUnsentQuery();

		$query->where('id', $id);

		return $query->first();
	}

	/**
	 *
	 * Return all Unsent Push notification objects
	 *
	 * @return \Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection
	 */
	public function getUnsentPushNotifications()
	{
		$query = $this->getUnsentQuery();

		return $query->get();
	}

	/**
	 *
	 * Build the unset push notifications query
	 *
	 * @return \Illuminate\Database\Eloquent\Builder
	 */
	protected function getUnsentQuery()
	{
		$query = PushNotification::query();

		$query->whereNull('sent_at');

		// for safety, we don't send any messages older than 1 hour from the scheduled time
		// this is to prevent any CRON errors, and the CRON to send older messages later on
		$query->where('scheduled_at', '>=', Carbon::now()->subHours(1));

		$query->where('scheduled_at', '<=', \Carbon\Carbon::now());
		// TODO: add timezone check

		// check for the recipient
		$query->where(function ($q) {
			$q->whereNotNull('topic');
			$q->orWhereNotNull('notifiable_id');
		});

		$query->orderBy('scheduled_at');

		return $query;
	}

}
