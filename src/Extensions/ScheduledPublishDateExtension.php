<?php

namespace Onfire\ScheduleCampaign\Extensions;

use Silverstripe\ORM\DataExtension;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\DateField;
use SilverStripe\ORM\FieldType\DBDate;

class ScheduledPublishDateExtension extends DataExtension
{
    private static $db = [
        'ScheduledPublishDate' => DBDate::class,
    ];

    /**
     * @param FieldList $fields
     */
    public function updateCMSFields(FieldList $fields)
    {
        $fields->addFieldToTab('Root.Main', DateField::create(
            'ScheduledPublishDate',
            'Scheduled Publish Date'
        ));
    }
}
