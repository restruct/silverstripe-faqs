<?php

namespace Restruct\FAQ\Model;

use Restruct\FAQ\Pages\FAQPage;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RelationEditor;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
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
    private static $many_many = [
        'Faqs' => FaqQuestion::class,
    ];

    /**
     * The order of the questions within this category, set by dragging them in the category's
     * "FAQ Questions" GridField.
     *
     * Called SortOrder up to 1.2.0, the same name as FaqQuestion's own $db field. The ORM then
     * returns the question's OWN value under that name when the relation is read, so the join
     * value was shadowed and GridFieldOrderableRows skipped rows it had to write (#5). An
     * extraField must never share its name with a $db field of the related class.
     *
     * @var array
     * @config
     */
    private static $many_many_extraFields = [
        'Faqs' => [
            //'SortOrder' => 'Int',
            'FaqSortOrder' => 'Int',
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
        # The join field, not FaqQuestion.SortOrder: see $many_many_extraFields (#5).
        //$config->addComponent(GridFieldOrderableRows::create('SortOrder'));
        $config->addComponent(GridFieldOrderableRows::create('FaqSortOrder'));

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
     * dev/build: carry the drag order of installs from before 1.3.0 over to the renamed join
     * field.
     */
    public function requireDefaultRecords()
    {
        parent::requireDefaultRecords();

        $this->migrateJoinSortOrder();
    }

    /**
     * One-off copy of the old join column SortOrder into FaqSortOrder (#5).
     *
     * dev/build adds FaqSortOrder and leaves the old SortOrder column where it is: it never drops
     * or renames a column that is no longer configured. So the old values are still there to copy,
     * and nothing writes to that column any more (ManyManyList::add() skips extra fields the
     * relation does not declare), which makes the copy safe to repeat: it only fills rows whose
     * FaqSortOrder is still 0 from an old SortOrder that is not, and a row that has been dragged
     * since (FaqSortOrder 1 or more) is never touched. Without the old column there is nothing to
     * do, as on a fresh install.
     *
     * @return int The number of join rows copied
     */
    public function migrateJoinSortOrder()
    {
        # Looked up rather than hard-coded: table_name is config a project can change.
        $table = static::getSchema()->manyManyComponent(self::class, 'Faqs')['join'];
        if (!DB::get_schema()->hasTable($table)) {
            return 0;
        }
        # Column names are case-insensitive in MySQL; fieldList() returns them as stored.
        $columns = array_change_key_case(DB::field_list($table), CASE_LOWER);
        if (!isset($columns['sortorder']) || !isset($columns['faqsortorder'])) {
            return 0;
        }

        DB::query(sprintf(
            'UPDATE "%s" SET "FaqSortOrder" = "SortOrder" WHERE "FaqSortOrder" = 0 AND "SortOrder" <> 0',
            $table
        ));
        $copied = DB::affected_rows();
        if ($copied) {
            DB::alteration_message(
                "$table: copied the question order of $copied rows from SortOrder to FaqSortOrder",
                'changed'
            );
        }

        return $copied;
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
