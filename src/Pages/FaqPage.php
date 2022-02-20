<?php

namespace Restruct\silverstripe\FAQs\Pages;

use Restruct\silverstripe\FAQs\Models\Faq;
use Restruct\silverstripe\FAQs\Models\FaqCategory;
use SilverStripe\Forms\HeaderField;
use Page;

class FaqPage extends Page
{

    private static $table_name = 'FaqPage';

    /**
     * @var string[]
     */
    private static $has_many = [
        'Faqs' => Faq::class,
    ];

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
