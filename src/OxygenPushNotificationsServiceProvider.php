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
            __DIR__ . '/../publish' => base_path(),
        ], 'oxygen::auto-publish');

		/*$this->publishes([
			__DIR__ . '/../publish/app/Entities/PushNotifications' => app_path('Entities/PushNotifications'),
			__DIR__ . '/../publish/app/Http/Controllers/Manage' => app_path('Http/Controllers/Manage'),
		], 'package-required-files');*/

        $this->setupNavItem();
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

    protected function setupNavItem()
    {
        // register the menu items
        $navItem = new NavItem('Push Notifications');
        $navItem->setResource('manage.push-notifications.index')
            ->setIconClass('fas fa-comment');

        Navigator::addItem($navItem, 'sidebar.manage');
    }
}
