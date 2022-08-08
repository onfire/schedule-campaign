<?php

namespace Onfire\ScheduleCampaign\Tasks;

use SilverStripe\Dev\BuildTask;
use SilverStripe\Versioned\ChangeSet;

class ScheduledPublishDateTask extends BuildTask
{
    public function run($request)
    {
        $countPublished = 0;
        $countReverted = 0;

        $now = date('Y-m-d');

        $sets = ChangeSet::get();

        if ($sets) {
//            foreach ($sets as $set) {
//                if ($set->State === 'open' && $set->ScheduledPublishDate !== NULL) {
//                    if ($now >= $set->ScheduledPublishDate) {
//                        $set->publish();
//                        $count++;
//                    }
//                }
//            }

            // Publish item, then close it
            foreach ($sets as $set) {
                if ($set->State === 'open' && $set->StartPublishDate !== NULL) {
                    if ($now >= $set->StartPublishDate) {
                        $set->publish();
                        $countPublished++;
                    }
                }
            }

            // Revert item, then close it
            foreach ($sets as $set) {
                if ($set->State === 'published' && $set->EndPublishDate !== NULL) {
                    if ($now <= $set->EndPublishDate) {
                        $set->revert();
                        $countReverted++;
                    }
                }
            }
        }

        echo $countPublished . ' sets published | ' . $countReverted . ' sets reverted';
    }
}
