<?php

namespace Restruct\SilverStripe\FAQs\Pages;

use Restruct\SilverStripe\FAQs\Models\Faq;
//use Restruct\SilverStripe\FAQs\Models\FaqCategory;
use SilverStripe\Forms\HeaderField;
use Page;
use SilverStripe\Lumberjack\Model\Lumberjack;

class FaqPage extends Page
{

    /**
     * @var string
     */
    private static $table_name = 'FaqPage';

    /**
     * @var string
     */
    private static $description = 'Frequently Asked Questions Page Holder';

    private static $extensions = [
        Lumberjack::class,
    ];

    private static $allowed_children = [
        Faq::class,
    ];

    public function getCMSFields()
    {

        $fields = parent::getCMSFields();
        //$fields->removeFieldFromTab('Root', 'Related FAQs');
        //$fields->addFieldToTab("Root.Main", HeaderField::create('managefaqs',
        //    'This page displays a filterable list of all FAQs, manage FAQs via "FAQs" in the left side menu.'),
        //    'Title');

        return $fields;

    }

    public function getLumberjackTitle()
    {
        return 'FAQs';
    }

}
