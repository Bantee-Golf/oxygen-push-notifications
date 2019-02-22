<?php


namespace EMedia\OxygenPushNotifications;


use EMedia\OxygenPushNotifications\Console\Commands\OxygenPushNotificationsPackageSetupCommand;
use Illuminate\Support\ServiceProvider;

class OxygenPushNotificationsServiceProvider extends ServiceProvider
{

	public function boot()
	{
		$this->loadViewsFrom(__DIR__ . '/../resources/views', 'oxygen-push-notifications');

		$this->publishes([
			__DIR__ . '/../PublishingFiles/app/Entities/PushNotifications' 	=> app_path('Entities/PushNotifications'),

			__DIR__ . '/../PublishingFiles/app/Http/Controllers/Manage' 		=> app_path('Http/Controllers/Manage'),
		], 'package-required-files');
	}

	public function register()
	{
		if (!app()->environment('production')) {
			$this->commands(OxygenPushNotificationsPackageSetupCommand::class);
		}
	}

}
