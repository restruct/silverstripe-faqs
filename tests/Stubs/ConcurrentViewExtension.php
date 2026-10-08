<?php

namespace Restruct\FAQ\Tests\Stubs;

use Restruct\FAQ\Model\FaqQuestion;
use SilverStripe\Core\Extension;
use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\Queries\SQLUpdate;

/**
 * Simulates a second visitor's view being counted while the endpoint handles the first one
 * (#3): just before a FaqQuestion is written, another request's increment lands in the database.
 * A read-ViewCount-add-one-write() endpoint then overwrites it with its stale value plus one.
 *
 * Only armed while a test sets $armed, and fires once, so it never affects other tests.
 */
class ConcurrentViewExtension extends Extension implements TestOnly
{
    /** @var bool */
    public static $armed = false;

    /** @var int how many concurrent increments were injected */
    public static $injected = 0;

    protected function onBeforeWrite()
    {
        if (!static::$armed) {
            return;
        }
        static::$armed = false;
        static::$injected++;

        $table = DataObject::getSchema()->tableName(FaqQuestion::class);
        SQLUpdate::create(
            sprintf('"%s"', $table),
            ['"ViewCount"' => ['"ViewCount" + 1' => []]],
            ['"ID"' => $this->owner->ID]
        )->execute();
    }
}
