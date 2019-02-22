<?php


namespace EMedia\OxygenPushNotifications\Entities\PushNotifications;

use EMedia\Formation\Entities\GeneratesFields;

use EMedia\OxygenPushNotifications\Domain\PushNotificationTopic;
use Illuminate\Database\Eloquent\Model;
use EMedia\QuickData\Entities\Traits\RelationshipDataTrait;
use EMedia\QuickData\Entities\Search\SearchableTrait;

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
		'sent_at',
		'topic',
	];

	protected $searchable = [
		'title',
		'content',
	];

	protected $editable = [
		[
			'name' => 'title',
			// 'type' => 'textarea',
			'placeholder' => 'Maximum 250 characters',
		],
		[
			'name' => 'message',
			// 'type' => 'textarea',
			'placeholder' => 'Maximum 250 characters',
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
			]
		]
	];

	protected $dates = [
		'scheduled_at',
		'sent_at',
		'read_at',
	];

	protected $rules = [
		'title' => 'required|min:10|max:500',
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

	public function getPushNotificationData()
	{
		return [
			'title' => $this->title,
			'content' => $this->message,
		];
	}
}
