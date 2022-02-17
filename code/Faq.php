<?php

class Faq extends DataObject {
	
    static $db = array(
		'Title' => 'Varchar(255)',
        'Content' => 'HTMLText',
		'CategoryList' => 'Varchar(255)', // to hold a list of many_many Categories for summary_fields
		'Sort' => 'Int',
		'ClickCount' => 'Int', // implement on Page (
    );
	
	//public static $default_sort = 'SortOrder';
	public static $securityEnabled = true;
	
//	public static $defaults = array(
//		"LangActive" => true,
//		'LangActive_en' => true,
//	);
	
//	static $multilingual_fields = array(
//		'Title', 'Content'//, 'LinkText'
//	);
	
	public static $has_one = array(
//		'ResourceImage' => 'Image',
//		'Attachment' => 'File',
	);
	
	// many_many added back by extension
	public static $belongs_many_many = array(
		'Categories'	=> 'FaqCategory',
		'Pages'			=> 'SiteTree',
    );
	
	public static $searchable_fields = array( 
		//'Categories', 
		'Title', 'CategoryList'
	);
	
	public static $summary_fields = array(
//		'Thumbnail' => 'Image',
//		'ResourceImage.CMSThumbnail' => 'Image', // Not workin in ModelAdmin
//		'ResourceImage.StripThumbnail' => 'Image', // Not workin in ModelAdmin
		'Title' => 'Title',
//		'Title_en' => 'Title EN',
//		'Description' => 'Description'
//		'CategorieListForGF' => 'Categories',
		'CategoryList' => 'Categories',
	);
	
	public function VoteLink(){
		//secure votes with a CSFR protection (copied from Form::getExtraFields());
		// @TODO; add ajax stuff to submit in background...
//		$securityToken->reset(); // optional; force-regenerate the securitytoken
		return Controller::curr()->Link().'faqvote/'.$this->ID.'/'.SecurityToken::getSecurityID();
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
	public function onBeforeWrite() {
		parent::onBeforeWrite();
		//Debug::dump( $this->Categories()->column('Title') );
		$this->CategoryList = implode(', ', $this->Categories()->column('Title'));
	}
	
	public function getCMSFields() {
		
		// Create new tabset & tabs;
		$fields = new FieldList();
		$fields->add(new TabSet("Root"));
		
		$fields->addFieldToTab("Root.Main", new TextField('Title', 'Question'));
		
		$ManyTagField = new ListboxField($name='Categories', null, 
				FaqCategory::get()->map('ID','Title')->toArray(), null, null, true);
		$fields->addFieldToTab("Root.Main", $ManyTagField);
		
		$fields->addFieldToTab("Root.Main", $answer = new HtmlEditorField('Content', "Answer"));
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
	public function canView($member = null){
		return Permission::check('CMS_ACCESS_CMSMain');
	}

	public function canEdit($member = null) {
		return $this->canView();
	}

	public function canDelete($member = null) {
		return $this->canView();
	}

	public function canCreate($member = null) {
		return $this->canView();
	}
	
}