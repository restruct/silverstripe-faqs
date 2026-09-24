<?php

namespace Restruct\FAQ\Pages;

use Restruct\FAQ\Model\FaqQuestion;
use Restruct\FAQ\PageControllers\FAQPageController;
// ArrayList/ArrayData are no longer imported: Silverstripe 6 moved them (ORM\ArrayList ->
// Model\List\ArrayList, View\ArrayData -> Model\ArrayData) and left no alias behind, so the
// class is resolved per major - see createArrayList()/createArrayData().
//use SilverStripe\ORM\ArrayList;
//use SilverStripe\View\ArrayData;
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
    private static $controller_name = FAQPageController::class;

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
     * Page-type description in the "add page" dialog. Read by Silverstripe 6, and by 5.4, which
     * prefers it over the deprecated $description below.
     *
     * @var string
     * @config
     */
    private static $class_description = 'A page that displays frequently asked questions organized by categories';

    /**
     * Silverstripe 5 before 5.4 reads only this name; Silverstripe 6 renamed it to
     * $class_description and ignores it. Delete when the module drops ^5.
     *
     * @var string
     * @config
     */
    private static $description = 'A page that displays frequently asked questions organized by categories';

    /**
     * Site-tree icon on Silverstripe 6 (renamed from $icon_class there).
     *
     * @var string
     * @config
     */
    private static $cms_icon_class = 'font-icon-help-circled';

    /**
     * Site-tree icon on Silverstripe 5, which knows no other name. Silverstripe 6 ignores it.
     * Delete when the module drops ^5.
     *
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
     * @return \SilverStripe\ORM\ArrayList|\SilverStripe\Model\List\ArrayList
     */
    public function getCategoriesWithFaqs()
    {
        $result = $this->createArrayList();

        // Sort categories by the SortOrder from the many_many relation
        $categories = $this->FaqCategories()->sort('SortOrder ASC');

        foreach ($categories as $category) {
            $faqs = $category->Faqs()->sort('SortOrder ASC');

            if ($faqs->count() > 0) {
                $result->push($this->createArrayData([
                    'Category' => $category,
                    'Faqs' => $faqs,
                ]));
            }
        }

        return $result;
    }

    /**
     * An empty ArrayList of whichever class the running Silverstripe major provides.
     *
     * @return \SilverStripe\ORM\ArrayList|\SilverStripe\Model\List\ArrayList
     */
    protected function createArrayList()
    {
        // Class names as strings, without a leading backslash: class_exists() on a name that
        // does not exist on this major is simply false, and the string never needs an import.
        $class = class_exists('SilverStripe\\Model\\List\\ArrayList')
            ? 'SilverStripe\\Model\\List\\ArrayList'   # Silverstripe 6
            : 'SilverStripe\\ORM\\ArrayList';         # Silverstripe 5
        return $class::create();
    }

    /**
     * An ArrayData of whichever class the running Silverstripe major provides.
     *
     * @return \SilverStripe\View\ArrayData|\SilverStripe\Model\ArrayData
     */
    protected function createArrayData(array $data)
    {
        $class = class_exists('SilverStripe\\Model\\ArrayData')
            ? 'SilverStripe\\Model\\ArrayData'        # Silverstripe 6
            : 'SilverStripe\\View\\ArrayData';        # Silverstripe 5
        return $class::create($data);
    }
}
