<?php

namespace Restruct\silverstripe\FAQs\Extensions;

use Restruct\silverstripe\FAQs\Models\Faq;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldAddExistingAutocompleter;
use SilverStripe\Forms\GridField\GridFieldConfig_RelationEditor;
use SilverStripe\ORM\DataExtension;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;

class SiteTreeExtension extends DataExtension
{

    // Applied to SiteTree to add relation back from Page;
    private static $many_many = [
        'Faqs' => Faq::class,
    ];

    // Add SortOrder so we can use SortableGridfield on pages;
    // $GFconf->addComponent(new GridFieldSortableRows('SortOrder'));
    private static $many_many_extraFields = [
        'Faqs' => [
            'PageSort' => 'Int',
        ],
    ];

//	// Add this method to the Pages in order to get the Faqs in the right SortOrder:
//	public function getFaqs(){
//		return $this->owner->getManyManyComponents('Faqs')->sort('FaqSortOrderForPage');
//	}

    /**
     * @param FieldList $fields
     *
     * @return void
     */
    public function updateCMSFields(FieldList $fields)
    {

        // Related FAQs
        $gfConfFaqs = GridFieldConfig_RelationEditor::create();
        //$gfConfFaqs->removeComponentsByType('GridFieldAddExistingAutocompleter');
        //$gfConfFaqs->addComponent(new GridFieldAddExistingPartialMatchAutocompleter('buttons-before-left'));
        //getComponentByType also gets any subclasses, so also the PartialMatchAutocompleter...
        $gfConfFaqs->getComponentByType(GridFieldAddExistingAutocompleter::class)
            ->setSearchFields([ 'Title:PartialMatch', 'CategoryList:PartialMatch' ])
            ->setResultsFormat('$Title ({$CategoryList})');
        //Sortorder was added by ExternalResourceSiteTreeExtension
        $gfConfFaqs->addComponent(new GridFieldOrderableRows('PageSort'));

        $fields->addFieldToTab("Root.Related FAQs", GridField::create("Faqs", "Related FAQs", $this->owner->Faqs(), $gfConfFaqs));
    }

}

