<?php

namespace Onfire\ScheduleCampaign\Tasks;

use SilverStripe\Dev\BuildTask;
use SilverStripe\Versioned\ChangeSet;
use SilverStripe\Versioned\ChangeSetItem;

class ScheduledPublishDateTask extends BuildTask
{
    public function run($request)
    {
        $count = 0;

        $now = date('Y-m-d');

        $sets = ChangeSet::get();
        $items = ChangeSetItem::get();

        if ($sets) {
            foreach ($sets as $set) {
                if ($set->State === 'open' && $set->StartPublishDate !== NULL) {
                    if ($now >= $set->StartPublishDate && $now < $set->EndPublishDate) {
                        $set->publish();
                        $count++;
                    }
                } elseif ($set->State === 'published' && $set->EndPublishDate !== NULL) {
                    if ($now >= $set->EndPublishDate) {

                        //$set->State = 'open';
                        //$set->write();

                        $setItems = $items->filter(['ChangeSetID' => $set->ID]);

                        foreach($setItems as $setItem) {
                            // $setItem->Object();
                            // $setItem->doUnpublish();
                            var_dump($setItem->Object());
                        }
                    }
                }
            }
        }

        echo $count . ' items updated';
    }
}
