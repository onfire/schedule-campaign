<?php

namespace Onfire\ScheduleCampaign\Extensions;

use Silverstripe\ORM\DataExtension;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\DateField;
use SilverStripe\ORM\FieldType\DBDate;

class ScheduledPublishDateExtension extends DataExtension
{
    private static $db = [
        'StartPublishDate' => DBDate::class,
        'EndPublishDate' => DBDate::class,
    ];

    /**
     * @param FieldList $fields
     */
    public function updateCMSFields(FieldList $fields)
    {
        $fields->addFieldToTab('Root.Main', DateField::create(
            'StartPublishDate',
            'Start Publish Date'
        ));

        $fields->addFieldToTab('Root.Main', DateField::create(
            'EndPublishDate',
            'End Publish Date'
        )->setDescription('Warning! If an end date is set, any changes made between the start and end date will be reverted on campaign end.'));
    }
}
