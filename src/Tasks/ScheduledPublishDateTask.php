<?php

namespace Onfire\ScheduleCampaign\Tasks;

use SilverStripe\Dev\BuildTask;
use SilverStripe\Versioned\ChangeSet;

class ScheduledPublishDateTask extends BuildTask
{
    public function run($request)
    {
        $count = 0;
        $now = date('Y-m-d');

        $sets = ChangeSet::get();

        if ($sets) {
            foreach ($sets as $set) {
                if ($set->State === 'open' && $set->ScheduledPublishDate !== NULL) {
                    if ($now >= $set->ScheduledPublishDate) {
                        $set->publish();
                        $count++;

                        echo 'time now ' . $now . ' | ' .  ' date saved to publish ' . $set->ScheduledPublishDate;
                    }
                }
            }
        }



        //echo $count . ' sets published';
    }
}
