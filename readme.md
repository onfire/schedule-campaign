# Silverstripe 4 Schedule Campaign
A basic silverstripe module to add a date to campaigns to be published at a future date.

## Features
* Custom DB field type
* Custom date fields added to campaign settings
* Custom task to be run via a daily cron job to check status and time and publish a campaign when parameters are met

## Requirements
* PHP 7.4 or greater (tested with up to PHP 7.4)
* Silverstripe 4

## Installation
```bash
composer require
```

## Usage
Add the following to you config file at `app/_config/mysite.yml`:
```php
Silverstripe\Versioned\ChangeSet:
  extensions:
    - Onfire\ScheduleCampaign\Extensions\ScheduledPublishDateExtension
```

Then run `dev/build`

Once module is installed setup a cron job to run a task daily at 1am, `ScheduledPublishDateTask: sake dev/tasks/ScheduledPublishDateTask`
