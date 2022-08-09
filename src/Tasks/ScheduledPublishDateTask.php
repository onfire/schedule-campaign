<?php

namespace Onfire\ScheduleCampaign\Tasks;

use SilverStripe\Dev\BuildTask;
use SilverStripe\Versioned\ChangeSet;
use SilverStripe\Versioned\ChangeSetItem;

// You can set a published item back to draft in a number of ways. I don't know if the code you've shown would work or not.
// My instinct is not since it's not actually altering the versioned DataObjects you're interested in - only the changesetitems
// that refer to them.

// You'll want to get $setItem->Object() and then perform an action on that - whether it's $object->doUnpublish() or
// $object->deleteFromStage(Versioned::LIVE) or $object->doRollbackTo($setItem->VersionBefore) or one of the other many ways
// to accomplish this, depending on whether you want before/after hooks to fire, new history to be added to indicate when
// the 'rollback' occurred, etc.

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
                            // $s->doUnpublish();





                            if ($setItem->ObjectClass === $setItem->Object()->ClassName) {
                                $setItem->Object()->doUnpublish();
                            }
                                //do sittress tuff



                            //var_dump($setItem->Object());
                        }
                    }
                }
            }
        }

        echo $count . ' items updated';
    }
}
