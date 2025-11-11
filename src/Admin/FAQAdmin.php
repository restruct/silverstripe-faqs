<?php

namespace Restruct\FAQ\Admin;

use Restruct\FAQ\Model\FaqQuestion;
use Restruct\FAQ\Model\FaqCategory;
use SilverStripe\Admin\ModelAdmin;
use SilverStripe\Forms\GridField\GridFieldConfig;
use SilverStripe\Forms\GridField\GridFieldExportButton;
use SilverStripe\Forms\GridField\GridFieldImportButton;
use SilverStripe\Forms\GridField\GridFieldPrintButton;

class FAQAdmin extends ModelAdmin
{
    /**
     * @var string
     * @config
     */
    private static $url_segment = 'faq';

    /**
     * @var string
     * @config
     */
    private static $menu_title = 'FAQs';

    public function i18n_singular_name()
    {
        return _t(__CLASS__ . '.MENUTITLE', 'FAQs');
    }

    /**
     * @var string
     * @config
     */
    private static $menu_icon_class = 'font-icon-help-circled';

    /**
     * @var array
     * @config
     */
    private static $managed_models = [
        FaqCategory::class,
        FaqQuestion::class,
    ];

    /**
     * @var int
     * @config
     */
    private static $menu_priority = 3;

    /**
     * Remove export, print and import buttons from GridField
     */
    protected function getGridFieldConfig(): GridFieldConfig
    {
        $config = parent::getGridFieldConfig();

        // Remove export, print and import buttons
        $config->removeComponentsByType(GridFieldExportButton::class);
        $config->removeComponentsByType(GridFieldPrintButton::class);
        $config->removeComponentsByType(GridFieldImportButton::class);

        return $config;
    }
}