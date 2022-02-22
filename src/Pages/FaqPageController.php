<?php

namespace Restruct\SilverStripe\FAQs\Pages;

use PageController;
use SilverStripe\Dev\Debug;
use SilverStripe\ORM\DataList;

class FaqPageController extends PageController
{

    private static $allowed_actions = [
//		'filter'
    ];

    public function Faqs($limit = null)
    {

        /*
        $filter = (int)$this->request->requestVar('filter');
        if ( !empty($filter) && $oFaqCategory = FaqCategory::get()->byID($filter) ) {
            return $oFaqCategory->Faqs()->sort('ClickCount DESC');
        }
        */

        // else just return all;

        /** @var DataList $oFAQs */
        $oFAQs = $this->Children();

        //Casting Limit to int, otherwise the $oFAQs will return null
       $limit = (int)$limit;

        //if limit is null, all records will be returned
        return $oFAQs->sort('ClickCount DESC')->limit($limit);
    }

    /*
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
    */

}
