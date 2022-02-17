<?php

class FaqSiteTreeExtension extends DataExtension {
	
	// Applied to SiteTree to add relation back from Page;
	public static $many_many = array(
		'Faqs' => 'Faq',
    );
	
	// Add SortOrder so we can use SortableGridfield on pages;
	// $GFconf->addComponent(new GridFieldSortableRows('SortOrder')); 
	public static $many_many_extraFields=array(
        'Faqs'=>array(
            'PageSort'=>'Int'
        )
    );
	
//	// Add this method to the Pages in order to get the Faqs in the right SortOrder:
//	public function getFaqs(){
//		return $this->owner->getManyManyComponents('Faqs')->sort('FaqSortOrderForPage');
//	}
	
	public function updateCMSFields(\FieldList $fields) {
		
		// Related FAQs
		$gfConfFaqs = GridFieldConfig_RelationEditor::create();
		//$gfConfFaqs->removeComponentsByType('GridFieldAddExistingAutocompleter');
		//$gfConfFaqs->addComponent(new GridFieldAddExistingPartialMatchAutocompleter('buttons-before-left'));
		//getComponentByType also gets any subclasses, so also the PartialMatchAutocompleter...		
		$gfConfFaqs->getComponentByType('GridFieldAddExistingAutocompleter')
				->setSearchFields(array('Title:PartialMatch', 'CategoryList:PartialMatch'))
				->setResultsFormat('$Title ({$CategoryList})');
		//Sortorder was added by ExternalResourceSiteTreeExtension
		$gfConfFaqs->addComponent(new GridFieldOrderableRows('PageSort'));
		$gridField = new GridField("Faqs", "Related FAQs", 
				$this->owner->Faqs(), $gfConfFaqs);
		$fields->addFieldToTab("Root.Related FAQs", $gridField);
	}
		
}

