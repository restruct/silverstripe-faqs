<?php

namespace Restruct\FAQ\Pages;

use Restruct\FAQ\Model\FaqQuestion;
use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;
use SilverStripe\ORM\DataList;
use Restruct\FAQ\Model\FaqCategory;
use Page;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RelationEditor;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;

class FAQPage extends Page
{
    /**
     * @var string
     * @config
     */
    private static $table_name = 'FAQPage';

    /**
     * @var string
     * @config
     */
    private static $singular_name = 'FAQ Page';

    /**
     * @var string
     * @config
     */
    private static $plural_name = 'FAQ Pages';

    /**
     * @var string
     * @config
     */
    private static $description = 'A page that displays frequently asked questions organized by categories';

    /**
     * @var string
     * @config
     */
    private static $icon_class = 'font-icon-help-circled';

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
    private static $many_many_extraFields = [
        'FaqCategories' => [
            'SortOrder' => 'Int',
        ],
    ];

    /**
     * @return array
     */
    public function fieldLabels($includerelations = true)
    {
        $labels = parent::fieldLabels($includerelations);

        $labels['FaqCategories'] = _t(__CLASS__ . '.FaqCategories', 'FAQ Categories');

        return $labels;
    }

    /**
     * @return FieldList
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        // Add sortable GridField for managing FAQ Categories
        $config = GridFieldConfig_RelationEditor::create();
        $config->addComponent(GridFieldOrderableRows::create('SortOrder'));

        $fields->addFieldToTab(
            'Root.FAQCategories',
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
     * Get all FAQs for the selected categories
     * @return DataList|null
     */
    public function getAllFaqs()
    {
        $categoryIDs = $this->FaqCategories()->column('ID');

        if (empty($categoryIDs)) {
            return null;
        }

        return FaqQuestion::get()
            ->filter('FaqCategories.ID', $categoryIDs)
            ->sort('SortOrder ASC');
    }

    /**
     * Get categories with their FAQs grouped
     * @return ArrayList
     */
    public function getCategoriesWithFaqs()
    {
        $result = ArrayList::create();

        // Sort categories by the SortOrder from the many_many relation
        $categories = $this->FaqCategories()->sort('SortOrder ASC');

        foreach ($categories as $category) {
            $faqs = $category->Faqs()->sort('SortOrder ASC');

            if ($faqs->count() > 0) {
                $result->push(ArrayData::create([
                    'Category' => $category,
                    'Faqs' => $faqs,
                ]));
            }
        }

        return $result;
    }
}