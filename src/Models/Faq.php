<?php

namespace Restruct\silverstripe\FAQs\Models;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Controller;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField;
use SilverStripe\Forms\ListboxField;
use SilverStripe\Forms\TabSet;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Permission;
use SilverStripe\Security\SecurityToken;
use Page;

/**
 * @property string     $CategoryList
 * @property mixed|null $ClickCount
 * @method DataList Categories()
 */
class Faq extends DataObject
{

    private static $table_name = 'Faq';

    private static $db = [
        'Title'      => 'Varchar(255)',
        'Content'    => 'HTMLText',
        'Sort'       => 'Int',
        //'CategoryList' => 'Varchar(255)', // to hold a list of many_many Categories for summary_fields
        'ClickCount' => 'Int', // implement on Page (
    ];

    //public static $default_sort = 'SortOrder';
    private static $securityEnabled = true;

    private static $has_one = [
        'Page' => Page::class,
    ];

    // many_many added back by extension
    private static $belongs_many_many = [
        'Categories' => FaqCategory::class,
        'Pages'      => SiteTree::class,
    ];

    private static $searchable_fields = [
        //'Categories',
        'Title',
        // @TODO; Test Searching
        //'CategoryList',
    ];

    private static $summary_fields = [
//		'Thumbnail' => 'Image',
//		'ResourceImage.CMSThumbnail' => 'Image', // Not workin in ModelAdmin
//		'ResourceImage.StripThumbnail' => 'Image', // Not workin in ModelAdmin
        'Title'        => 'Title',
//		'Title_en' => 'Title EN',
//		'Description' => 'Description'
//		'CategorieListForGF' => 'Categories',
        'CategoryList' => 'Categories',
    ];

    private static $casting = [
        'CategoryList' => 'Varchar(255)', // to hold a list of many_many Categories for summary_fields
    ];

    public function getCategoryList()
    {
        return implode(', ', $this->Categories()->column('Title'));
    }

    public function VoteLink()
    {
        //secure votes with a CSFR protection (copied from Form::getExtraFields());
        // @TODO; add ajax stuff to submit in background...
//		$securityToken->reset(); // optional; force-regenerate the securitytoken
        return Controller::curr()->Link() . 'faqvote/' . $this->ID . '/' . SecurityToken::getSecurityID();
        // method added by FAQ page extension...
    }


//	// Needed for the CategorieListForGF to be picked up in Summary
//	public static $casting = array(
//		"CategorieListForGF" => 'Varchar',
//	);
//	function CategorieListForGF() {
//		if ($this->Categories()->exists())
//			$cats = $this->Categories()->toArray();
//			return implode(', ', $cats);
//	}

    // Instead, we're writing the
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        //Debug::dump( $this->Categories()->column('Title') );

        // Moved this to $casting
        //$this->CategoryList = implode(', ', $this->Categories()->column('Title'));
    }

    public function getCMSFields()
    {

        // Create new tabset & tabs;
        $fields = FieldList::create();
        $fields->add(new TabSet("Root"));

        $fields->addFieldToTab("Root.Main", TextField::create('Title', 'Question'));

        $oCategories = FaqCategory::get();
        $oCategoryMap = $oCategories ? $oCategories->map('ID', 'Title')->toArray() : [];
        $oCategoryListField = ListboxField::create('Categories', null);
        $oCategoryListField->setSource($oCategoryMap);
        $fields->addFieldToTab("Root.Main", $oCategoryListField);

        $fields->addFieldToTab("Root.Main", $answer = HTMLEditorField::create('Content', "Answer"));
        $answer->setRows(20);
        //$answer->setColumns(4); // Not working?

        $this->extend('updateCMSFields', $fields);

        return $fields;

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
