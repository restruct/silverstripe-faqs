<?php

namespace Restruct\FAQ\Tests;

use Restruct\FAQ\Model\FaqCategory;
use Restruct\FAQ\Model\FaqQuestion;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DB;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;

/**
 * The two DataObjects: schema, write behaviour, display helpers and CMS fields.
 */
class FaqModelTest extends SapphireTest
{
    protected static $fixture_file = 'FaqTest.yml';

    /**
     * dev/build must produce both tables and the join table with its SortOrder extra field.
     * Checked against the live schema, not against config, so a relation that is declared but
     * never built fails here.
     */
    public function testSchemaIsBuilt(): void
    {
        $schema = DB::get_schema();

        $this->assertTrue($schema->hasTable('FaqQuestion'));
        $this->assertTrue($schema->hasTable('FaqCategory'));
        $this->assertTrue($schema->hasTable('FaqCategory_Faqs'));

        $question = $schema->fieldList('FaqQuestion');
        foreach (['Question', 'Answer', 'SortOrder', 'ViewCount'] as $field) {
            $this->assertArrayHasKey($field, $question, "FaqQuestion.$field");
        }
        $this->assertArrayHasKey('Title', $schema->fieldList('FaqCategory'));

        $join = $schema->fieldList('FaqCategory_Faqs');
        foreach (['FaqCategoryID', 'FaqQuestionID', 'SortOrder'] as $field) {
            $this->assertArrayHasKey($field, $join, "FaqCategory_Faqs.$field");
        }
    }

    public function testRelationsAreWiredBothWays(): void
    {
        $returns = $this->objFromFixture(FaqQuestion::class, 'q_returns');
        $this->assertEqualsCanonicalizing(
            ['Zulu orders', 'Alpha money'],
            $returns->FaqCategories()->column('Title')
        );

        $orders = $this->objFromFixture(FaqCategory::class, 'cat_orders');
        $this->assertEqualsCanonicalizing(
            ['How long does shipping take?', 'Can I return an item?'],
            $orders->Faqs()->column('Question')
        );
    }

    public function testNewQuestionIsAppendedAfterTheHighestSortOrder(): void
    {
        // Fixture maximum is 50 (q_elsewhere).
        $question = FaqQuestion::create(['Question' => 'Appended']);
        $question->write();

        $this->assertSame(51, (int) $question->SortOrder);
    }

    public function testFirstQuestionEverGetsSortOrderOne(): void
    {
        foreach (FaqQuestion::get() as $existing) {
            $existing->delete();
        }

        $question = FaqQuestion::create(['Question' => 'First']);
        $question->write();

        $this->assertSame(1, (int) $question->SortOrder);
    }

    public function testExplicitSortOrderIsKept(): void
    {
        $question = FaqQuestion::create(['Question' => 'Pinned', 'SortOrder' => 5]);
        $question->write();

        $this->assertSame(5, (int) $question->SortOrder);
    }

    public function testTitlesComeFromQuestionAndCategoryTitle(): void
    {
        $this->assertSame(
            'Can I return an item?',
            $this->objFromFixture(FaqQuestion::class, 'q_returns')->getTitle()
        );
        $this->assertSame(
            'Zulu orders',
            $this->objFromFixture(FaqCategory::class, 'cat_orders')->getTitle()
        );
    }

    public function testCategoriesListNamesEveryCategory(): void
    {
        $list = $this->objFromFixture(FaqQuestion::class, 'q_returns')->getCategoriesList();
        $parts = explode(', ', $list);

        $this->assertEqualsCanonicalizing(['Zulu orders', 'Alpha money'], $parts);
    }

    public function testCategoriesListSaysSoWhenThereAreNone(): void
    {
        $this->assertSame(
            'No categories assigned',
            $this->objFromFixture(FaqQuestion::class, 'q_orphan')->getCategoriesList()
        );
    }

    public function testViewTokenIsTheCurrentSecurityToken(): void
    {
        $token = $this->objFromFixture(FaqQuestion::class, 'q_returns')->getViewToken();

        $this->assertNotEmpty($token);
        $this->assertSame(\SilverStripe\Security\SecurityToken::inst()->getValue(), $token);
    }

    public function testQuestionCmsFields(): void
    {
        $fields = $this->objFromFixture(FaqQuestion::class, 'q_returns')->getCMSFields();

        // Managed elsewhere, so not editable here.
        $this->assertNull($fields->dataFieldByName('SortOrder'));
        $this->assertNull($fields->dataFieldByName('ViewCount'));

        $question = $fields->dataFieldByName('Question');
        $this->assertInstanceOf(TextField::class, $question);
        $this->assertSame('Question', $question->Title());

        $answer = $fields->dataFieldByName('Answer');
        $this->assertInstanceOf(HTMLEditorField::class, $answer);
        $this->assertSame(15, (int) $answer->getRows());

        // The categories GridField sits on its own tab rather than the scaffolded one.
        $categories = $fields->fieldByName('Root.Categories.FaqCategories');
        $this->assertInstanceOf(GridField::class, $categories);
        $this->assertSame(FaqCategory::class, $categories->getModelClass());
    }

    public function testCategoryCmsFieldsOrderQuestionsByTheJoinSortOrder(): void
    {
        $fields = $this->objFromFixture(FaqCategory::class, 'cat_orders')->getCMSFields();

        $grid = $fields->fieldByName('Root.FAQQuestions.Faqs');
        $this->assertInstanceOf(GridField::class, $grid);
        $this->assertSame(FaqQuestion::class, $grid->getModelClass());

        $orderable = $grid->getConfig()->getComponentByType(GridFieldOrderableRows::class);
        $this->assertNotNull($orderable, 'Questions in a category are drag-sortable');
        $this->assertSame('SortOrder', $orderable->getSortField());

        // The scaffolded relation field is replaced, not duplicated on Root.Faqs.
        $this->assertNull($fields->fieldByName('Root.Faqs'));
    }

    public function testAnswerEditorRendersTheStoredAnswer(): void
    {
        // getCMSFields() returns fields without values; the CMS loads them through a form.
        // Rendering through one catches an editor field that builds but cannot render.
        $question = $this->objFromFixture(FaqQuestion::class, 'q_returns');
        $form = Form::create(null, 'EditForm', $question->getCMSFields(), FieldList::create());
        $form->loadDataFrom($question);

        $html = (string) $form->Fields()->dataFieldByName('Answer')->Field();

        $this->assertStringContainsString('Within 30 days.', html_entity_decode($html));
    }
}
