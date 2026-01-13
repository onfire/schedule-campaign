<?php

namespace Onfire\ScheduleCampaign\Tasks;

use SilverStripe\Dev\BuildTask;
use SilverStripe\Versioned\ChangeSet;
use SilverStripe\Versioned\ChangeSetItem;
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

            // Handle sets that can be published (open or reverted)
            if ($set->State === ChangeSet::STATE_OPEN && $start) {
                if ($this->isInPublishWindow($start, $end, $now)) {
                        $set->sync();
                        $set->publish();
                        $publishCount++;
                }
            }
            // Handle sets that need to be reverted (already published & end date passed)
            elseif ($set->State === ChangeSet::STATE_PUBLISHED && $end) {
                if ($now >= $end) {
                    $setItems = ChangeSetItem::get()->filter(['ChangeSetID' => $set->ID]);

                    foreach ($setItems as $setItem) {
                        $object = $setItem->Object();
                        if (!$object || !$setItem->isVersioned()) continue;

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

        $message = "{$publishCount} campaigns published | {$unpublishedCount} campaign objects unpublished";

        // Log and echo
        $logger = Injector::inst()->get(LoggerInterface::class);
        $logger->info($message);
        echo $message;
    }

    /**
     * Determine if the current time is within the publish window
     */
    protected function isInPublishWindow(?int $start, ?int $end, int $now): bool
    {
        $hasStarted  = $start && $now >= $start;
        $notEndedYet = !$end || $now < $end;

        return $hasStarted && $notEndedYet;
    }
}
