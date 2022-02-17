<?php
class FaqPage extends Page {
	
	function getCMSFields(){
		
		$fields = parent::getCMSFields();
		$fields->removeFieldFromTab('Root', 'Related FAQs');
		$fields->addFieldToTab("Root.Main", new HeaderField('managefaqs',
				'This page displays a filterable list of all FAQs, manage FAQs via "FAQs" in the left side menu.'),
				'Title');
		
		return $fields;
		
	}
	
	public function FaqCategories(){
		return FaqCategory::get();
	}
	
}

class FaqPage_Controller extends Page_Controller {
	
	/**
	 * An array of actions that can be accessed via a request. Each array element should be an action name, and the
	 * permissions or conditions required to allow the user to access it.
	 *
	 * <code>
	 * array (
	 *     'action', // anyone can access this action
	 *     'action' => true, // same as above
	 *     'action' => 'ADMIN', // you must have ADMIN permissions to access this action
	 *     'action' => '->checkAction' // you can only access this action if $this->checkAction() returns true
	 * );
	 * </code>
	 *
	 * @var array
	 */
	public static $allowed_actions = array (
//		'filter'
	);
	
	public function init() {
		parent::init();
	}
	
	public function Faqs(){
		
		// activate all for english
//		$contacts = Faq::get();
//		foreach( $contacts as $cont ){
//			$cont->LangActive_en = true;
//			$cont->write();
//		}
		
		if( $this->request->requestVar('filter') ){
			if( $cat = FaqCategory::get()->byID( (int) $this->request->requestVar('filter') ) ){
				return $cat->Faqs()->sort('ClickCount DESC');
			}
		}
		// else just return all;
		return Faq::get()->sort('ClickCount DESC');
	}
	
	// for usage in templates
//	public function Faqs(){
//		return $this->getFilteredFaqs;
//	}
	
	// action to be callable;
//	public function filter(){
//		//actual filtering in $this->Faqs();
//		return $this;
//	}
	
	public function FaqCatDropdown(){
		$DrDown = new DropdownField(
			'filter',
			'filter',
			array_filter( FaqCategory::get()->sort('Title')->map('ID','Title')->toArray() )
		);
		$DrDown->setEmptyString( _t('FAQ.ALLCATEGORIES','All categories') );
		if( $this->request->requestVar('filter') ){
			$DrDown->setValue( $this->request->requestVar('filter') );
		}
		$DrDown->setAttribute('onchange', 'this.form.submit()');
		$DrDown->addExtraClass(' form-control');
		return $DrDown;
	}
	
}