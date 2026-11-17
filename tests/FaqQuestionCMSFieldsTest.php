<?php

namespace Restruct\FAQ\Tests;

use Restruct\FAQ\Model\FaqQuestion;
use SilverStripe\Core\Extension;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\FieldList;

/**
 * A project extension's updateCMSFields() runs after the module's own field changes, so it can change the answer
 * editor and remove the Categories tab (it used to run before them, and the module undid its changes).
 */
class FaqQuestionCMSFieldsTest extends SapphireTest
{
    protected $usesDatabase = true;

    protected static $required_extensions = [
        FaqQuestion::class => [FaqQuestionCMSFieldsTest_Extension::class],
    ];

    public function testExtensionSeesTheModuleFields(): void
    {
        $fields = FaqQuestion::create()->getCMSFields();

        $this->assertSame(4, (int) $fields->dataFieldByName('Answer')->getAttribute('rows'));
        $this->assertNull($fields->fieldByName('Root.Categories'));
    }
}

class FaqQuestionCMSFieldsTest_Extension extends Extension implements TestOnly
{
    public function updateCMSFields(FieldList $fields): void
    {
        $fields->dataFieldByName('Answer')->setRows(4);
        $fields->removeByName('Categories');
    }
}
