<?php

namespace Restruct\silverstripe\FAQs\CMS;

use Restruct\silverstripe\FAQs\Models\Faq;
use Restruct\silverstripe\FAQs\Models\FaqCategory;
use SilverStripe\Admin\ModelAdmin;
use SilverStripe\Forms\GridField\GridField;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;

class FaqAdmin extends ModelAdmin
{

    // Can manage multiple models
    private static $managed_models = [
        Faq::class,
        FaqCategory::class,
    ];

    // which should be sortable;
    private static $sortable_models = [
        Faq::class,
        FaqCategory::class,
    ];

    // Linked as /admin/leftemails/
    private static $url_segment = 'faqs';

    /**
     * @var string
     */
    private static $menu_title = 'FAQs';

    public $showImportForm = false;

    /**
     * @param $id
     * @param $fields
     *
     * @return \SilverStripe\Forms\Form
     */
    public function getEditForm($id = null, $fields = null)
    {
        $form = parent::getEditForm($id = null, $fields = null);
        foreach ( static::$sortable_models as $model ) {
            /** @var GridField $oGridField */
            if ( $oGridField = $form->Fields()->fieldByName($model) ) {
                $oGridField->getConfig()->addComponent(new GridFieldOrderableRows());
            }
        }

        return $form;
    }

}
