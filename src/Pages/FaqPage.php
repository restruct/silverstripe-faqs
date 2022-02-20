<?php

namespace Restruct\silverstripe\FAQs\Pages;

use Restruct\silverstripe\FAQs\Models\FaqCategory;
use SilverStripe\Forms\HeaderField;
use Page;
use PageController;

class FaqPage extends Page
{

    public function getCMSFields()
    {

        $fields = parent::getCMSFields();
        $fields->removeFieldFromTab('Root', 'Related FAQs');
        $fields->addFieldToTab("Root.Main", HeaderField::create('managefaqs',
            'This page displays a filterable list of all FAQs, manage FAQs via "FAQs" in the left side menu.'),
            'Title');

        return $fields;

    }

    public function FaqCategories()
    {
        return FaqCategory::get();
    }

}
