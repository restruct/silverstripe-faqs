<?php

namespace Restruct\silverstripe\FAQs\Models;

use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TabSet;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Permission;

class FaqCategory extends DataObject
{

    private static $db = [
        'Title' => 'Varchar',
        'Sort'  => 'Int',
    ];


    private static $many_many = [
        'Faqs' => Faq::class,
    ];

    private static $defaults = [];

    private static $summary_fields = [
        'Title',
    ];

    private static $searchable_fields = [
        'Title',
    ];

    public function getCMSFields()
    {

        // Create new tabset & tabs;
        $fields = FieldList::create();
        $fields->add(new TabSet("Root"));

        $fields->addFieldToTab("Root.Main", TextField::create('Title'));
//		$fields->addFieldToTab("Root.Main", new SiteTreeURLSegmentField('IconName'));

        $this->extend('updateCMSFields', $fields);

        return $fields;

    }

    // Update all related FAQs if category title changed
    public function onAfterWrite()
    {
        parent::onAfterWrite();

        //Moved Faq->CategoryList to $casting
        /**
        foreach ( $this->Faqs() as $Faq ) {
            $Faq->write();
        }*/
    }

    /*
     * Base permissions:
     * ADMIN = all
     * CMS_ACCESS_LeftAndMain = all CMS
     * CMS_ACCESS_CMSMain = pages
     * CMS_ACCESS_SecurityAdmin = users
     * CMS_ACCESS_MediaAdmin = media/files
     * CMS_ACCESS_ReportAdmin = reports
     */
    public function canView($member = null)
    {
        return Permission::check('CMS_ACCESS_CMSMain');
    }

    public function canEdit($member = null)
    {
        return $this->canView();
    }

    public function canDelete($member = null)
    {
        return $this->canView();
    }

    public function canCreate($member = null, $context = [])
    {
        return $this->canView();
    }

}
