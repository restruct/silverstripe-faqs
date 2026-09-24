<?php

namespace Restruct\FAQ\Tests;

use Restruct\FAQ\Admin\FAQAdmin;
use Restruct\FAQ\Model\FaqCategory;
use Restruct\FAQ\Model\FaqQuestion;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\Session;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldExportButton;
use SilverStripe\Forms\GridField\GridFieldImportButton;
use SilverStripe\Forms\GridField\GridFieldPrintButton;

class FAQAdminTest extends SapphireTest
{
    protected $usesDatabase = true;

    public function testManagesBothModels(): void
    {
        $this->assertSame(
            [FaqCategory::class, FaqQuestion::class],
            array_keys(FAQAdmin::singleton()->getManagedModels())
        );
    }

    // Two plain test methods rather than a data provider: the provider metadata differs between
    // PHPUnit 9 (SS5, docblock annotation) and PHPUnit 11 (SS6, attribute, annotation deprecated).
    public function testCategoryGridHasNoExportPrintOrImportButtons(): void
    {
        $this->assertGridHasNoExportPrintOrImportButtons(FaqCategory::class);
    }

    public function testQuestionGridHasNoExportPrintOrImportButtons(): void
    {
        $this->assertGridHasNoExportPrintOrImportButtons(FaqQuestion::class);
    }

    private function assertGridHasNoExportPrintOrImportButtons(string $model): void
    {
        $this->logInWithPermission('ADMIN');

        // Route the admin to the model's tab the way a request to admin/faq/<Model> would, so
        // init() selects the model itself.
        $request = new HTTPRequest('GET', 'admin/faq/' . $this->sanitise($model));
        $request->setRouteParams(['ModelClass' => $this->sanitise($model)]);
        $request->setSession(new Session([]));
        // LeftAndMain::init() reaches SudoModeController, which fetches the request from the
        // Injector rather than from the controller; register it as the real kernel would.
        Injector::inst()->registerService($request, HTTPRequest::class);
        $admin = FAQAdmin::create();
        $admin->setRequest($request);
        $admin->doInit();
        $this->assertSame($model, $admin->getModelClass());

        $grid = $admin->getEditForm()->Fields()->dataFieldByName($this->sanitise($model));
        $this->assertInstanceOf(GridField::class, $grid);

        $config = $grid->getConfig();
        $this->assertNull($config->getComponentByType(GridFieldExportButton::class));
        $this->assertNull($config->getComponentByType(GridFieldPrintButton::class));
        $this->assertNull($config->getComponentByType(GridFieldImportButton::class));
    }

    private function sanitise(string $class): string
    {
        return str_replace('\\', '-', $class);
    }
}
