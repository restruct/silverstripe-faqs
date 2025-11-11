<?php

namespace Restruct\FAQ\Model;

use Restruct\FAQ\Pages\FAQPage;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RelationEditor;
use SilverStripe\ORM\DataObject;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;

class FaqCategory extends DataObject
{
    /**
     * @var string
     * @config
     */
    private static $table_name = 'FaqCategory';

    /**
     * @var string
     * @config
     */
    private static $default_sort = 'Title';

    /**
     * @var array
     * @config
     */
    private static $db = [
        'Title' => 'Varchar(255)',
    ];

    /**
     * @var array
     * @config
     */
    private static $belongs_many_many = [
        'Faqs' => FaqQuestion::class . '.FaqCategories',
        'FAQPages' => FAQPage::class,
    ];

    /**
     * @var array
     * @config
     */
    private static $many_many_extraFields = [
        'Faqs' => [
            'SortOrder' => 'Int',
        ],
    ];

    /**
     * @var array
     * @config
     */
    private static $summary_fields = [
        'Title',
    ];

    /**
     * @return array
     */
    public function fieldLabels($includerelations = true)
    {
        $labels = parent::fieldLabels($includerelations);

        $labels['Title'] = _t(__CLASS__ . '.Title', 'Title');
        $labels['Faqs'] = _t(__CLASS__ . '.Faqs', 'FAQs');

        return $labels;
    }

    /**
     * @return FieldList
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        // Remove default fields
        $fields->removeByName('Faqs');
        $fields->removeByName('FAQPages');

        // Add sortable GridField for FAQ Questions
        $config = GridFieldConfig_RelationEditor::create();
        $config->addComponent(GridFieldOrderableRows::create('SortOrder'));

        $gridField = GridField::create(
            'Faqs',
            _t(__CLASS__ . '.GridFieldTitle', 'FAQ Questions'),
            $this->Faqs(),
            $config
        );

        $fields->addFieldToTab('Root.FAQQuestions', $gridField);

        return $fields;
    }

    /**
     * Get the title for display
     * @return string
     */
    public function getTitle()
    {
        return $this->getField('Title');
    }
}