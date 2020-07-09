<?php


namespace EMedia\OxygenPushNotifications;


use App\Entities\PushNotifications\PushNotificationsRepository;
use EMedia\OxygenPushNotifications\Console\Commands\SendPushNotificationsQueueCommand;
use EMedia\OxygenPushNotifications\Console\Commands\SubscribeDevicesToTopic;
use EMedia\OxygenPushNotifications\Console\Commands\OxygenPushNotificationsPackageSetupCommand;
use EMedia\OxygenPushNotifications\Console\Commands\TestPushNotificationsCommand;
use Illuminate\Support\ServiceProvider;

class OxygenPushNotificationsServiceProvider extends ServiceProvider
{

	public function boot()
	{
		$this->loadViewsFrom(__DIR__ . '/../resources/views', 'oxygen-push-notifications');

		$this->publishes([
			__DIR__ . '/../PublishingFiles/app/Entities/PushNotifications' 	=> app_path('Entities/PushNotifications'),

			__DIR__ . '/../PublishingFiles/app/Http/Controllers/Manage' 		=> app_path('Http/Controllers/Manage'),

			__DIR__ . '/../PublishingFiles/app/Http/Controllers/API' 		=> app_path('Http/Controllers/API'),

		], 'package-required-files');
	}

	public function register()
	{
		if (!app()->environment('production')) {
			$this->commands(OxygenPushNotificationsPackageSetupCommand::class);
			$this->commands(TestPushNotificationsCommand::class);
		}

		$this->mergeConfigFrom( __DIR__ . '/../config/features.php', 'features');

		$this->commands(SubscribeDevicesToTopic::class);

		if (class_exists(PushNotificationsRepository::class)) {
			$this->commands(SendPushNotificationsQueueCommand::class);
		}
	}

}
