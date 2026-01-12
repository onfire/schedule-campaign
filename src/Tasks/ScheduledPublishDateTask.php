<?php

namespace Onfire\ScheduleCampaign\Tasks;

use SilverStripe\Dev\BuildTask;
use SilverStripe\Versioned\ChangeSet;
use SilverStripe\Core\Injector\Injector;
use Psr\Log\LoggerInterface;
use SilverStripe\CMS\Model\SiteTree;

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

            // Open or reverted sets that need publishing
            if (($set->State === ChangeSet::STATE_OPEN || $set->State === ChangeSet::STATE_REVERTED) && $start) {
                if ($this->isInPublishWindow($start, $end, $now)) {
                    $setItems = $set->Items();
                    foreach ($setItems as $setItem) {
                        $object = $setItem->Object();
                        if (!$object) {
                            continue;
                        }

                        // Use recursive publish for SiteTree pages to include children
                        if ($object instanceof SiteTree) {
                            $object->publishRecursive();
                        } else {
                            $object->doPublish();
                        }

                        $publishCount++;
                    }

                    $set->State = ChangeSet::STATE_PUBLISHED;
                    $set->write();
                }
            }
            // Already published sets that have ended — unpublish or rollback items
            elseif ($set->State === ChangeSet::STATE_PUBLISHED && $end) {
                if ($now >= $end) {
                    $setItems = $set->Items();
                    foreach ($setItems as $setItem) {
                        $object = $setItem->Object();
                        if (!$object) {
                            continue;
                        }

                        if (!$setItem->VersionBefore) {
                            $object->doUnpublish();
                        } else {
                            $object->rollbackRecursive($setItem->VersionBefore);
                            if ($object->exists()) {
                                $object instanceof SiteTree
                                    ? $object->publishRecursive()
                                    : $object->doPublish();
                            }
                        }

                        $unpublishedCount++;
                    }

                    $set->State = ChangeSet::STATE_REVERTED;
                    $set->write();
                }
            }
        }

        $message = "ScheduledPublishDateTask: {$publishCount} campaigns published | {$unpublishedCount} campaign objects unpublished";

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
