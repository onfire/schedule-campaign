<?php

namespace Onfire\ScheduleCampaign\Tasks;

use SilverStripe\Dev\BuildTask;
use SilverStripe\Versioned\ChangeSet;
use SilverStripe\Core\Injector\Injector;
use Psr\Log\LoggerInterface;

class ScheduledPublishDateTask extends BuildTask
{
    private static $segment = 'ScheduledCampaignsTask';

    public function run($request)
    {
        $publishCount = 0;
        $unpublishedCount = 0;

        $now = time();

        $sets = ChangeSet::get();

        foreach ($sets as $set) {
            $start = $set->StartPublishDate ? strtotime($set->StartPublishDate) : null;
            $end   = $set->EndPublishDate ? strtotime($set->EndPublishDate) : null;

            if ($set->State === ChangeSet::STATE_OPEN && $start) {
                if ($this->isInPublishWindow($start, $end, $now)) {
                    $set->sync();
                    $set->publish();
                    $publishCount++;
                }
            } elseif ($set->State === ChangeSet::STATE_PUBLISHED && $end) {
                $setItems = $set->Items(); // ORM relation, more efficient

                foreach ($setItems as $setItem) {
                    $object = $setItem->Object();
                    if (!$object) {
                        continue; // skip if object no longer exists
                    }

                    if (!$setItem->VersionBefore) {
                        $object->doUnpublish();
                    } else {
                        $object->rollbackRecursive($setItem->VersionBefore);
                        if ($object->exists()) {
                            $object->doPublish();
                        }
                    }

                    $unpublishedCount++;
                }

                $set->State = ChangeSet::STATE_REVERTED;
                $set->write();
            }
        }

        $setName = $set->Title ?? "ID {$set->ID}";
        $message = "Set '{$setName}': {$publishCount} published | {$unpublishedCount} unpublished";

        $logger = Injector::inst()->get(LoggerInterface::class);
        $logger->info($message);

        echo $message;
    }

    /**
     * Determine if the current time is within the publish window
     */
    protected function isInPublishWindow(?int $start, ?int $end, int $now): bool
    {
        $hasStarted = $start && $now >= $start;
        $notEndedYet = !$end || $now < $end;

        return $hasStarted && $notEndedYet;
    }
}
