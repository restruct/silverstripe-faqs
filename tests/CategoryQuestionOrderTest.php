<?php

namespace Restruct\FAQ\Tests;

use ReflectionMethod;
use Restruct\FAQ\Model\FaqCategory;
use Restruct\FAQ\Model\FaqQuestion;
use Restruct\FAQ\Pages\FAQPage;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\ORM\DB;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;

/**
 * Regression for #5: dragging questions within a category was sometimes not saved.
 *
 * The category's join field was called SortOrder, like FaqQuestion's own $db field. The ORM then
 * hands back the question's OWN value under that name (the join value is shadowed), and
 * GridFieldOrderableRows::reorderItems() only writes a join row whose value differs from the new
 * position, so it compared against the wrong value and skipped rows that needed a write.
 *
 * Every helper reads the join field's name from the GridField's GridFieldOrderableRows, so these
 * tests run unchanged against the old field name and fail there for the reason above, not for a
 * missing column.
 */
class CategoryQuestionOrderTest extends SapphireTest
{
    protected $usesDatabase = true;

    /** @var FaqCategory */
    private $category;

    /** @var array question name => ID */
    private $ids = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->category = FaqCategory::create(['Title' => 'Returns']);
        $this->category->write();
        # Created A, B, C in this order, so onBeforeWrite() gives them their own SortOrder 1, 2, 3.
        foreach (['A', 'B', 'C'] as $name) {
            $question = FaqQuestion::create(['Question' => $name, 'Answer' => "<p>$name</p>"]);
            $question->write();
            $this->ids[$name] = $question->ID;
            # Added the way the CMS grid adds a record: no extra fields, so the join value is 0.
            $this->category->Faqs()->add($question);
        }
    }

    /**
     * A record read through the relation carries the JOIN value under the join field's name,
     * which is the value GridFieldOrderableRows renders into its hidden inputs and compares with.
     */
    public function testRelationRecordsCarryTheJoinValue(): void
    {
        # Join and own values disagree: join C=1, A=2, B=3; own A=1, B=2, C=3.
        $this->setJoinValues(['C' => 1, 'A' => 2, 'B' => 3]);
        $field = $this->sortField();

        $viaRelation = [];
        foreach ($this->category->Faqs() as $record) {
            $viaRelation[$record->Question] = (int) $record->getField($field);
        }
        ksort($viaRelation);

        $this->assertSame(['A' => 2, 'B' => 3, 'C' => 1], $viaRelation);
    }

    /**
     * The scenario from the issue: drag C to the top (saved: C, A, B), then drag it back to the
     * bottom, which is the questions' own order. The second drag used to be skipped row by row.
     */
    public function testDraggingBackIntoTheQuestionsOwnOrderIsSaved(): void
    {
        $this->drag(['C', 'A', 'B']);
        $this->assertSame(['C', 'A', 'B'], $this->joinOrder(), 'first drag is saved');

        $this->drag(['A', 'B', 'C']);
        $this->assertSame(['A', 'B', 'C'], $this->joinOrder(), 'dragging back is saved');
    }

    /**
     * Every drag from the issue also shows on the FAQ page, which reads the join field through
     * FAQPage::getCategoriesWithFaqs().
     */
    public function testTheFaqPageFollowsEachDrag(): void
    {
        $page = FAQPage::create(['Title' => 'FAQ', 'URLSegment' => 'faq-order']);
        $page->write();
        $page->FaqCategories()->add($this->category);

        foreach ([['C', 'A', 'B'], ['A', 'B', 'C'], ['B', 'C', 'A']] as $order) {
            $this->drag($order);
            $item = FAQPage::get()->byID($page->ID)->getCategoriesWithFaqs()->first();
            $this->assertSame($order, $item->Faqs->column('Question'), 'page after drag to ' . implode(', ', $order));
        }
    }

    /**
     * One drag in the CMS: the hidden sort inputs as getColumnContent() renders them, renumbered
     * as the grid's JavaScript (rebuildSort) does after the drop, then handed to executeReorder(),
     * which is what handleReorder() runs on the posted values.
     */
    private function drag(array $newOrder): void
    {
        $grid = $this->grid();
        /** @var GridFieldOrderableRows $orderable */
        $orderable = $grid->getConfig()->getComponentByType(GridFieldOrderableRows::class);

        # getColumnContent() renders $record->getField($sortField) into each row's hidden input.
        $hidden = [];
        foreach ($grid->getManipulatedList() as $record) {
            $hidden[$record->ID] = (int) $record->getField($orderable->getSortField());
        }
        # rebuildSort(): start at the lowest positive value shown (at least 1), then count up in
        # the new row order.
        $positive = array_filter($hidden, function ($value) {
            return $value > 0;
        });
        $sort = max(1, $positive ? min($positive) : 1);
        $sortedIDs = [];
        foreach ($newOrder as $name) {
            $sortedIDs[$sort++] = $this->ids[$name];
        }

        # executeReorder() is protected in gridfieldextensions 4 and 5; handleReorder() needs a
        # whole CMS request around it for nothing this test is about.
        $method = new ReflectionMethod($orderable, 'executeReorder');
        $method->setAccessible(true);
        $this->assertTrue($method->invoke($orderable, $grid, $sortedIDs));
    }

    /**
     * The category's "FAQ Questions" GridField, built exactly as getCMSFields() builds it, on a
     * freshly fetched category.
     */
    private function grid(): GridField
    {
        $category = FaqCategory::get()->byID($this->category->ID);
        $grid = $category->getCMSFields()->dataFieldByName('Faqs');
        $this->assertInstanceOf(GridField::class, $grid);
        return $grid;
    }

    /**
     * The join field the GridField sorts on.
     */
    private function sortField(): string
    {
        return $this->grid()->getConfig()->getComponentByType(GridFieldOrderableRows::class)->getSortField();
    }

    private function setJoinValues(array $values): void
    {
        $table = $this->category->Faqs()->getJoinTable();
        foreach ($values as $name => $value) {
            DB::prepared_query(
                sprintf('UPDATE "%s" SET "%s" = ? WHERE "FaqCategoryID" = ? AND "FaqQuestionID" = ?', $table, $this->sortField()),
                [$value, $this->category->ID, $this->ids[$name]]
            );
        }
    }

    /**
     * The question names ordered by the join value stored in the database.
     */
    private function joinOrder(): array
    {
        $table = $this->category->Faqs()->getJoinTable();
        $values = DB::prepared_query(
            sprintf('SELECT "FaqQuestionID", "%s" FROM "%s" WHERE "FaqCategoryID" = ?', $this->sortField(), $table),
            [$this->category->ID]
        )->map();
        $byName = [];
        foreach ($this->ids as $name => $id) {
            $byName[$name] = (int) $values[$id];
        }
        asort($byName);
        return array_keys($byName);
    }
}
