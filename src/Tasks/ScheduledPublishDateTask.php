<?php

namespace Onfire\ScheduleCampaign\Tasks;

use SilverStripe\Dev\BuildTask;
use SilverStripe\Versioned\ChangeSet;
use SilverStripe\Versioned\ChangeSetItem;

class ScheduledPublishDateTask extends BuildTask
{
    private static $segment = 'ScheduledCampaignsTask';

    protected $description = 'A task run via cron job to publish campaigns and unpublished objects within a set timeframe';

    public function run($request)
    {
        $publishCount = 0;
        $unpublishedCount = 0;

        $now = date('Y-m-d');

        $sets = ChangeSet::get();
        $items = ChangeSetItem::get();

        if ($sets) {
            foreach ($sets as $set) {
                if ($set->State === 'open' && $set->StartPublishDate !== NULL) {
                    if ($now >= $set->StartPublishDate && $now < $set->EndPublishDate) {
                        $set->publish();
                        $publishCount++;
                    }
                } elseif ($set->State === 'published' && $set->EndPublishDate !== NULL) {
                    if ($now >= $set->EndPublishDate) {

                        $set->State = 'open';
                        $set->write();

                        $setItems = $items->filter(['ChangeSetID' => $set->ID]);

                        foreach($setItems as $setItem) {
                            $setItem->Object()->doUnpublish();
                            $unpublishedCount++;
                        }
                    }
                }
            }
        }

        echo $publishCount . ' items published | ' . $unpublishedCount . ' objects set to draft';
    }
}
