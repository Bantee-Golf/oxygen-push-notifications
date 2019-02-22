<?php


namespace EMedia\OxygenPushNotifications\Console\Commands;


use EMedia\Helpers\Console\Commands\Packages\BasePackageSetupCommand;
use EMedia\OxygenPushNotifications\OxygenPushNotificationsServiceProvider;

class OxygenPushNotificationsPackageSetupCommand extends BasePackageSetupCommand
{

	protected $signature = 'setup:package:oxygen-push-notifications';

	protected $description = 'Push notifications management package for Oxygen';

	protected $packageName = 'Push Notifications';

	protected $updateRoutesFile = true;

	/**
	 *
	 * Any name to display to the user
	 *
	 * @return mixed
	 */
	protected function generateMigrations()
	{
		$this->copyMigrationFile(__DIR__, '001_create_push_notifications_table.php', \CreatePushNotificationsTable::class);
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
	}
}
