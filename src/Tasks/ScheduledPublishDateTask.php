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
        $totalPublished = 0;
        $totalUnpublished = 0;

        $now = time();

        $sets = ChangeSet::get();

        foreach ($sets as $set) {
            $start = $set->StartPublishDate ? strtotime($set->StartPublishDate) : null;
            $end   = $set->EndPublishDate ? strtotime($set->EndPublishDate) : null;

            // Allow publishing for sets that are OPEN or REVERTED
            if (($set->State === ChangeSet::STATE_OPEN || $set->State === ChangeSet::STATE_REVERTED) && $start) {
                if ($this->isInPublishWindow($start, $end, $now)) {
                    $set->sync();
                    $set->publish();
                    $totalPublished++;

                    // If it was reverted before, mark it as published
                    if ($set->State === ChangeSet::STATE_REVERTED) {
                        $set->State = ChangeSet::STATE_PUBLISHED;
                        $set->write();
                    }
                }
            }

            // Handle unpublishing past the EndPublishDate
            elseif ($set->State === ChangeSet::STATE_PUBLISHED && $end && $now >= $end) {
                $setItems = $set->Items(); // ORM relation

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

                    $totalUnpublished++;
                }

                // Mark the ChangeSet as reverted
                $set->State = ChangeSet::STATE_REVERTED;
                $set->write();
            }
        }

        // Logging & feedback
        $message = "Campaigns published: {$totalPublished} | Campaign objects unpublished: {$totalUnpublished}";

        $logger = Injector::inst()->get(LoggerInterface::class);
        $logger->info($message);

        echo $message . PHP_EOL;
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
