<?php

namespace Restruct\FAQ\Model;

use Restruct\FAQ\Controllers\FaqApiController;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RelationEditor;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\SecurityToken;

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
     * Categories in the CMS: the question's Categories tab, the summary column and the FAQs admin's
     * categories tab. Switch off on sites that only use questions (e.g. picked by a content block); the
     * relation and FAQPage keep working.
     *
     * @config
     */
    private static $show_categories = true;

    /**
     * @var array
     * @config
     */
    private static $db = [
        'Question'  => 'Varchar(255)',
        'Answer'    => 'HTMLText',
        'SortOrder' => 'Int',
        'ViewCount' => 'Int',
    ];

    /**
     * @var array
     * @config
     */
    private static $belongs_many_many = [
        'FaqCategories' => FaqCategory::class . '.Faqs',
    ];

    /**
     * @var array
     * @config
     */
    private static $summary_fields = [
        'Question',
        'ViewCount',
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
        $labels['ViewCount'] = _t(__CLASS__ . '.ViewCount', 'View Count');
        $labels['FaqCategories'] = _t(__CLASS__ . '.FaqCategories', 'FAQ Categories');
        $labels['CategoriesList'] = _t(__CLASS__ . '.FaqCategories', 'FAQ Categories');

        return $labels;
    }

    /**
     * @return FieldList
     */
    /**
     * Without categories in the CMS (show_categories: false), no categories column either
     */
    public function summaryFields()
    {
        $fields = parent::summaryFields();
        if ( ! static::config()->get('show_categories')) {
            unset($fields['CategoriesList']);
        }

        return $fields;
    }

    public function getCMSFields()
    {
        // The module's own field changes run before updateCMSFields(), so a project extension sees (and can
        // change) the finished form: the answer editor, the Categories tab. They used to run after it and
        // silently undid an extension's changes to those fields.
        $this->beforeUpdateCMSFields(function (FieldList $fields) {
            $this->addModuleCMSFields($fields);
        });

        return parent::getCMSFields();
    }

    /**
     * The module's changes to the scaffolded fields (see getCMSFields())
     */
    protected function addModuleCMSFields(FieldList $fields): void
    {
        // Remove SortOrder - managed automatically by GridFieldOrderableRows
        // Remove ViewCount - managed automatically by frontend tracking
        $fields->removeByName('SortOrder');
        $fields->removeByName('ViewCount');
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

        // Add GridField for managing FAQ Categories (not on sites that do not use categories)
        if ( ! static::config()->get('show_categories')) {
            return;
        }
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

    /**
     * Get a secure token for tracking views
     * This token is used to validate AJAX requests for incrementing view counts
     * @return string
     */
    public function getViewToken()
    {
        // Outside a request (CLI tasks, publishing a block that renders questions for the search index) there is
        // no session to read a token from, and no visitor to count (#8)
        // has_curr() is gone in SS6 (curr() returns null there); on SS5 it avoids curr()'s warning on an empty stack
        $controller = (method_exists(Controller::class, 'has_curr') && !Controller::has_curr()) ? null : Controller::curr();
        if (!Injector::inst()->has(HTTPRequest::class) && !$controller) {
            return '';
        }
        $token = SecurityToken::inst();
        return $token->getValue();
    }

    /**
     * The URL faq-view-tracker.js posts a view to, rendered into the toggle's
     * data-view-tracking-url attribute. Root-relative and under the site's base URL, so view
     * counting also works on a site installed in a subdirectory (#2); the script used to post to
     * the fixed path /faq-api/incrementView.
     *
     * @return string
     */
    public function getViewTrackingURL()
    {
        # FaqApiController::Link() is its url_segment plus the action, without the base URL.
        return Controller::join_links(
            Director::baseURL(),
            FaqApiController::singleton()->Link('incrementView')
        );
    }
}
