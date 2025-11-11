<?php

namespace Restruct\FAQ\Model;

use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RelationEditor;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataObject;

class FaqQuestion extends DataObject
{
    /**
     * @var string
     * @config
     */
    private static $table_name = 'FaqQuestion';

    /**
     * @var string
     * @config
     */
    private static $default_sort = 'SortOrder ASC, ID ASC';

    /**
     * @var array
     * @config
     */
    private static $db = [
        'Question'  => 'Varchar(255)',
        'Answer'    => 'HTMLText',
        'SortOrder' => 'Int',
    ];

    /**
     * @var array
     * @config
     */
    private static $many_many = [
        'FaqCategories' => FaqCategory::class,
    ];

    /**
     * @var array
     * @config
     */
    private static $summary_fields = [
        'Question',
        'CategoriesList',
    ];

    /**
     * @var array
     * @config
     */
    private static $searchable_fields = [
        'Question',
        'Answer',
        'FaqCategories.Title',
    ];

    /**
     * @return array
     */
    public function fieldLabels($includerelations = true)
    {
        $labels = parent::fieldLabels($includerelations);

        $labels['Question'] = _t(__CLASS__ . '.Question', 'Question');
        $labels['Answer'] = _t(__CLASS__ . '.Answer', 'Answer');
        $labels['SortOrder'] = _t(__CLASS__ . '.SortOrder', 'Sort Order');
        $labels['FaqCategories'] = _t(__CLASS__ . '.FaqCategories', 'FAQ Categories');
        $labels['CategoriesList'] = _t(__CLASS__ . '.FaqCategories', 'FAQ Categories');

        return $labels;
    }

    /**
     * @return FieldList
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        // Remove SortOrder - managed automatically by GridFieldOrderableRows
        $fields->removeByName('SortOrder');
        $fields->removeByName('FaqCategories');

        // Make Question field a TextField
        $fields->replaceField(
            'Question',
            TextField::create('Question', _t(__CLASS__ . '.Question', 'Question'))
        );

        // Make Answer field an HTML Editor
        $fields->replaceField(
            'Answer',
            HTMLEditorField::create('Answer', _t(__CLASS__ . '.Answer', 'Answer'))
                ->setRows(15)
        );

        // Add GridField for managing FAQ Categories
        $config = GridFieldConfig_RelationEditor::create();

        $fields->addFieldToTab(
            'Root.Categories',
            GridField::create(
                'FaqCategories',
                _t(__CLASS__ . '.FaqCategories', 'FAQ Categories'),
                $this->FaqCategories(),
                $config
            )
        );

        return $fields;
    }

    /**
     * Get the title for display
     * @return string
     */
    public function getTitle()
    {
        return $this->Question;
    }

    /**
     * Get a comma-separated list of categories for display in GridField
     * @return string
     */
    public function getCategoriesList()
    {
        $categories = $this->FaqCategories();
        if ($categories && $categories->count() > 0) {
            return implode(', ', $categories->column('Title'));
        }
        return _t(__CLASS__ . '.NoCategoriesAssigned', 'No categories assigned');
    }

    /**
     * Event handler called before writing to the database.
     */
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();

        // Set default sort order if not set
        if (!$this->SortOrder) {
            $maxSort = static::get()->max('SortOrder');
            $this->SortOrder = $maxSort ? $maxSort + 1 : 1;
        }
    }
}
