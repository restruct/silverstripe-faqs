<?php

class FaqCategory extends DataObject {
	
	static $db = array(
        'Title' => 'Varchar',
		'Sort' => 'Int',
	);
	
	//public static $default_sort = 'SortOrder';
	
	static $many_many = array(
		'Faqs' => 'Faq',
	);
	
//	static $multilingual_fields = array(
//		'Title'
//	);
	
	public static $defaults = array(
//		"LangActive" => true,
//		'LangActive_en' => true,
	);
	
	public static $summary_fields = array(
		'Title'
	);
	
	public static $searchable_fields = array( 
		'Title'
	);
	
//	static $casting = array( 
//		//'ImageNice' => 'HTMLText', 
//		'Title' => 'Varchar' 
//	); 
	
	public function getCMSFields() {
		
		// Create new tabset & tabs;
		$fields = new FieldList();
		$fields->add(new TabSet("Root"));
		
		$fields->addFieldToTab("Root.Main", new TextField('Title'));
//		$fields->addFieldToTab("Root.Main", new SiteTreeURLSegmentField('IconName'));
		
		$this->extend('updateCMSFields', $fields);
		return $fields;
		
	}
	
	// Update all related FAQs if category title changed
	public function onAfterWrite() {
		parent::onAfterWrite();
		foreach( $this->Faqs() as $Faq ){
			$Faq->write();
		}
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