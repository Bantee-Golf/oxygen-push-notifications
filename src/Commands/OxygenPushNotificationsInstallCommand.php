<?php

namespace EMedia\OxygenPushNotifications\Commands;

use ElegantMedia\OxygenFoundation\Console\Commands\ExtensionInstallCommand;
use EMedia\OxygenPushNotifications\OxygenPushNotificationsServiceProvider;

class OxygenPushNotificationsInstallCommand extends ExtensionInstallCommand
{
    protected $signature = 'oxygen:push-notifications:install';

    protected $description = 'Setup the Oxygen Push notifications management package';

    protected $requiredServiceProviders = [
        'Kreait\Laravel\Firebase\ServiceProvider',
    ];

    public function getExtensionServiceProvider(): string
    {
        return OxygenPushNotificationsServiceProvider::class;
    }

    public function getExtensionDisplayName(): string
    {
        return 'Push Notifications';
    }

	/**
	 *
	 * Any name to display to the user
	 *
	 * @return mixed
	 *
	protected function generateMigrations()
	{
		$this->copyMigrationFile(__DIR__, '001_create_push_notifications_table.php', \CreatePushNotificationsTable::class);

		$this->copyMigrationFile(__DIR__, '002_add_topic_subscriptions_to_devices_table.php', \AddTopicSubscriptionsToDevicesTable::class);
	}

	protected function generateSeeds()
	{
		$this->copySeedFile(__DIR__, 'PushNotificationsTableSeeder.php', \PushNotificationsTableSeeder::class);
	}

	protected function publishPackageFiles()
	{
		$this->call('vendor:publish', [
			'--provider' => OxygenPushNotificationsServiceProvider::class,
			'--tag' => 'package-required-files'
		]);
	}*/
}
