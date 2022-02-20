<?php

namespace Restruct\silverstripe\FAQs\Pages;

use PageController;
use Restruct\silverstripe\FAQs\Models\Faq;
use Restruct\silverstripe\FAQs\Models\FaqCategory;
use SilverStripe\Forms\DropdownField;

class FaqPageController extends PageController
{

    private static $allowed_actions = [
//		'filter'
    ];

    public function Faqs()
    {

        $filter = (int)$this->request->requestVar('filter');
        if ( !empty($filter) && $oFaqCategory = FaqCategory::get()->byID($filter) ) {
            return $oFaqCategory->Faqs()->sort('ClickCount DESC');
        }

        // else just return all;
        return Faq::get()->sort('ClickCount DESC');
    }

    public function FaqCatDropdown()
    {
        $oFaqCategories = FaqCategory::get();
        $oFaqCategoryMap = $oFaqCategories ? $oFaqCategories->sort('Title')->map('ID', 'Title')->toArray() : [];
        $oFilterDropdownField = DropdownField::create('filter', 'filter');
        $oFilterDropdownField->setSource($oFaqCategoryMap);
        $oFilterDropdownField->setEmptyString(_t('FAQ.ALLCATEGORIES', 'All categories'));
        if ( $this->request->requestVar('filter') ) {
            $oFilterDropdownField->setValue($this->request->requestVar('filter'));
        }
        $oFilterDropdownField->setAttribute('onchange', 'this.form.submit()');
        $oFilterDropdownField->addExtraClass(' form-control');

        return $oFilterDropdownField;
    }

}
