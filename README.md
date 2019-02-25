# Oxygen Push Notifications

This package allows you to:

- Send push notifications to an individual device.
- Send push notifications to a User (to all devices that belongs to a User).
- Send push notifications to all devices (broadcast), without looping or locking resources.
- Send push notifications to only iOS devices.
- Send push notifications to only Android devices.
- Bulk register all devices to Broadcast topics.
- Create and send push notifications to custom topics.
- Subscribe devices to topics.
- Unsubscribe devices from topics.
- Scheduled notifications to be sent out at a later date and time.

### Send Push Notifications

All notifications are stored in the database and can be processed later as a queue. Notifications can be assigned to a User, Device or a Topic.

First, create the push notification Model.

```
use \EMedia\OxygenPushNotifications\Domain\PushNotificationManager;

$user = \App\User::find(1);

$push = new PushNotification([
	'title' => 'My Notification',
	'message' => 'My Message',
]);
$push->scheduled_at = now();    // schedule for now or a later date

// OPTION 1: send to a user
$push->notifiable()->associate($user);

// OPTION 2: send to a device
// $push->notifiable()->associate($user->devices->first());

// OPTION 3: send to a topic
// $push->topic('all_devices');

$push->save();

// Send the notification.
// When the line below is called, the notification is sent immidiately to the recipient. The scheduled time will be ignored. If you need to send at a schedule, see the command below.
PushNotificationManager::sendStoredPushNotification($push);
```

#### Sending Scheduled Push Notifications
 
Add the following command in Laravel Scheduler to run every minute or so.

```
// Using CLI
php artisan oxygen:push-notifications-send

// Using Scheduler
$schedule->command('oxygen:push-notifications-send')->everyMinute();
```

To send an indidual notification (for testing or debugging), pass the notification Id.

```
php artisan oxygen:push-notifications-send --id=5
```

### Other API Commands

```
// Send notification to a user immediately
PushNotificationManager::sendPushNotificationToUser($user, $push, $extraData = [], $apnsConfig = null, $androidConfig = null)

// Send notification to a device immediately
PushNotificationManager::sendPushNotificationToDevice($device, $push, $extraData = [], $apnsConfig = null, $androidConfig = null)

// Send notification to a topic immediately
PushNotificationManager::sendPushNotificationToTopic($topicName, $push, $extraData = [], $apnsConfig = null, $androidConfig = null)
```

### Registering Devices to Topics

There are 3 pre-defined topics.

- All Devices
- IOS Devices
- Android Devices

This will let you to send messages to all devices with just 1 line of code without looping through all devices.

The devices do not get automatically subscribed to topics. You must do this in code per device, or use the bulk subscription command below.

```
// Subscribe device to `all_devices` AND the correct device type topic.
// Call this during `register` AND `login` calls, so the device gets subscribed to right topics.
PushNotificationManager::subscribeDeviceToBroadcastTopics($device);

// NOTE: If the device is listed as subscribed in the database, it doesn't connect to FCM. This is for faster performance. If you need to always connect to firebase and subscribe, add the $force flag.
PushNotificationManager::subscribeDeviceToBroadcastTopics($device, $force);

// Alternatively, you can register devices to custom topics
PushNotificationManager::subscribeDeviceToBroadcastTopics($device, $topicName);

// You can also subscribe a collection of devices to a topic
PushNotificationManager::subscribeDevicesToTopic($devices, $topicName);

// Unsubscribe devices
PushNotificationManager::unsubscribeDeviceFromTopic($device, $topicName);
PushNotificationManager::unsubscribeDevicesFromTopic($devices, $topicName);
```

**UNSUBSCRIBE WARNING:** Each device has two database fields called, `is_subscribed_to_all_devices_topic` and `is_subscribed_to_device_type_topic`. When you're unsubscribing devices from topics, if they are one of the pre-defined topics, ensure the database flags are properly updated as well.

If you already have devices stored in the database, the devices can be bulk subscribed to topics. To subscribe devices, call the following artisan command.

```
// subsribe all devices
php artisan oxygen:push-notifications-subscribe-devices --topic=all_devices

// subsribe iOS devices
php artisan oxygen:push-notifications-subscribe-devices --topic=ios_devices

// subsribe Android devices
php artisan oxygen:push-notifications-subscribe-devices --topic=android_devices
```


### Installation

Update your `composer.json` and add these repositories.

```
"repositories": [
    {
        "type": "vcs",
        "url": "git@bitbucket.org:elegantmedia/oxygen-push-notifications.git"
    }
    {
        "type":"vcs",
        "url":"git@bitbucket.org:elegantmedia/formation.git"
    },
    {
        "type":"vcs",
        "url":"git@bitbucket.org:elegantmedia/quickdata-laravel.git"
    },
    {
        "type": "vcs",
        "url": "git@bitbucket.org:elegantmedia/laravel-helpers.git"
    },
    {
        "type": "vcs",
        "url": "git@bitbucket.org:elegantmedia/php-helpers.git"
    },
    {
        "type": "vcs",
        "url": "git@bitbucket.org:elegantmedia/devices-laravel.git"
    }
],
```

Install the package

```
composer require emedia/oxygen-push-notifications
```

Add Menu Item to Oxygen (Optional)

On `sidebar.blade.php`, add the menu item. 

```
<li><a href="{{ route('manage.push-notifications.index') }}"><i class="fas fa-comment"></i> Push Notifications</a></li>
```

### Setup

This package uses [Firebase Admin SDK](https://firebase-php.readthedocs.io/en/stable/) to connect to Firebase Cloud Messaging.

For authentication, you must use a Google Service Account. [Check their documentation](https://firebase-php.readthedocs.io/en/stable/setup.html) for latest instructions. They are repeated here for easier reference.

##### STEP 1. Create a Service Account

1. Login to [Firebase Console](https://console.firebase.google.com/) and select your project.
1. Go to `Project Settings` > `Service Accounts` > `Firebase Admin SDK` > `Manage all service accounts`
1. Select the previously created account to send Push Notifications or Select or `Create Service Account`
1. Give the permissions as a `Project > Editor`.
1. Create a new key, and download the `json` file.

##### STEP 2. Link the key to project

1. Store the key file in `storage/keys` or a similar location.
1. Add the file path to `.gitignore` file.
1. Update the `.env` file as below.

```
GOOGLE_APPLICATION_CREDENTIALS="/www/sites/myproject/storage/keys/fcm_keys.json"
```

The `GOOGLE_APPLICATION_CREDENTIALS` variable is required, and the path must be the full path to the key from root (not relative path).

# **IMPORTANT: READ THIS!**
- **DO NOT STORE they key** in `public_html`, `storage/public` or any other public paths.
- **DO NOT COMMIT the key** to Git history.
- **DO NOT KEEP production keys** on a local or staging environment. Using production keys on staging or local machine, can cause you to broadcast push notifications accidentally to live app users! Use separate Firebase projects for testing and production.

### Bugs/Errors?

Create a new branch and submit a pull-request with a fix.