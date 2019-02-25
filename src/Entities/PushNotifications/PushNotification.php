<?php


namespace EMedia\OxygenPushNotifications\Entities\PushNotifications;

use EMedia\Formation\Entities\GeneratesFields;

use EMedia\OxygenPushNotifications\Domain\PushNotificationTopic;
use Illuminate\Database\Eloquent\Model;
use EMedia\QuickData\Entities\Traits\RelationshipDataTrait;
use EMedia\QuickData\Entities\Search\SearchableTrait;
use Kreait\Firebase\Messaging\Notification;

class PushNotification extends Model implements PushNotificationInterface
{

	use SearchableTrait, RelationshipDataTrait;
	use GeneratesFields;

	protected $fillable = [
		'title',
		'message',
		'badge_count',
		'data',
		'read_at',
		'scheduled_at',
		'scheduled_timezone',
		'topic',
	];

	protected $searchable = [
		'title',
		'content',
	];

	protected $editable = [
		[
			'name' => 'title',
			'placeholder' => 'Maximum 100 characters',
			'attributes' => [
				'required' => true,
				'minlength' => 10,
				'maxlength' => 100,
			]
		],
		[
			'name' => 'message',
			'type' => 'textarea',
			'placeholder' => 'Maximum 500 characters',
			'attributes' => [
				'required' => true,
				'minlength' => 10,
				'maxlength' => 500,
			]
		],
		[
			'name' => 'topic',
			'display_name' => 'Topic',
			'type' => 'select',
			'options' => [
				'' => 'Select Topic',
				PushNotificationTopic::TOPIC_ALL_DEVICES 	 => 'All Devices',
				PushNotificationTopic::TOPIC_IOS_DEVICES 	 => 'All iOS Devices',
				PushNotificationTopic::TOPIC_ANDROID_DEVICES => 'All Android Devices',
			],
			'attributes' => [
				'required' => true,
			]
		],
	];

	protected $dates = [
		'scheduled_at',
		'sent_at',
		'read_at',
	];

	protected $rules = [
		'title' => 'required|min:10|max:100',
		'message' => 'required|min:10|max:500',
		'topic' => 'required',
	];

	protected $manyToManyRelations = [];

	/**
	 *  Setup model event hooks
	 */
	public static function boot()
	{
		parent::boot();
		self::creating(function ($model) {
			$model->uuid = (string) \Webpatser\Uuid\Uuid::generate(4);
		});
	}


	public function notifiable()
	{
		return $this->morphTo('notifiable');
	}

	/**
	 *
	 * Return a Cloud Notification Object
	 *
	 * @return Notification
	 */
	public function getCloudNotification()
	{
		return Notification::fromArray([
			'title' => $this->title,
			'body' => $this->message,
		]);
	}

	/**
	 *
	 * Return APNS Config
	 *
	 * @return array
	 */
	public function getApnsConfigAttribute()
	{
		if (empty($this->apns_config)) return [];

		try {
			return json_decode($this->apns_config, true);
		} catch (\Exception $ex) {
			//
		}

		return [];
	}

	/**
	 *
	 * Return Android Config
	 *
	 * @return array
	 */
	public function getAndroidConfigAttribute()
	{
		if (empty($this->apns_config)) return [];

		try {
			return json_decode($this->android_config, true);
		} catch (\Exception $ex) {
			//
		}

		return [];
	}

	/**
	 *
	 * Return Data
	 *
	 * @return array
	 */
	public function getDataAttribute()
	{
		if (empty($this->apns_config)) return [];

		try {
			return json_decode($this->data, true);
		} catch (\Exception $ex) {
			//
		}

		return [];
	}

	public function setDataConfigAttribute($value)
	{
		$this->attributes['data'] = json_encode($value);
	}

	public function setApnsConfigAttribute($value)
	{
		$this->attributes['apns_config'] = json_encode($value);
	}

	public function setAndroidConfigAttribute($value)
	{
		$this->attributes['android_config'] = json_encode($value);
	}

	public function getTopicDisplayNameAttribute()
	{
		return str_replace('_', ' ', strtoupper($this->topic));
	}

	/**
	 *
	 * Allow updating the sent timestamp
	 *
	 * @param null $model
	 *
	 * @return void
	 */
	public function touchSentTimestamp($updateTimestamp = true)
	{
		if ($updateTimestamp) {
			$this->sent_at = now();
			$this->save();
		}
	}
}
