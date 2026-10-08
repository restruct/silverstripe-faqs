<?php

namespace Restruct\FAQ\Tests\Stubs;

use Restruct\FAQ\Model\FaqQuestion;
use SilverStripe\Core\Extension;
use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\Queries\SQLUpdate;

/**
 * Simulates a second visitor's view being counted while the endpoint handles the first one
 * (#3). Applied to FaqApiController, it lands another request's increment in the database through
 * the onBeforeIncrementView hook: after the endpoint has loaded the question (and with it the
 * ViewCount it then holds), before it counts this view. An endpoint that counts from that loaded
 * value instead of in the database overwrites the other request's increment.
 *
 * It used to hook FaqQuestion::onBeforeWrite(), which the atomic endpoint never calls, so it could
 * only fail the old read-add-write() code and stayed green for a non-atomic SQL update.
 *
 * Only armed while a test sets $armed, and fires once, so it never affects other tests.
 */
class ConcurrentViewExtension extends Extension implements TestOnly
{
    /** @var bool */
    public static $armed = false;

    /** @var int how many concurrent increments were injected */
    public static $injected = 0;

    /**
     * @param FaqQuestion $faq the question as the endpoint loaded it
     */
    protected function onBeforeIncrementView($faq)
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
            ['"ID"' => $faq->ID]
        )->execute();
    }
}
