<?php

namespace Restruct\FAQ\Tests;

use Restruct\FAQ\Model\FaqCategory;
use Restruct\FAQ\Model\FaqQuestion;
use Restruct\FAQ\Pages\FAQPage;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\DB;

/**
 * The 1.3.0 upgrade (#5): the category->questions join field SortOrder became FaqSortOrder, and
 * FaqCategory::requireDefaultRecords() copies the old column's values over once.
 *
 * Each test recreates a 1.2.0 join table by adding the old SortOrder column back. That is DDL,
 * which MySQL commits implicitly, so the test cannot run inside a rolled-back transaction: with
 * $usesTransactions off, SapphireTest drops the temporary database after every test instead.
 */
class FaqSortOrderMigrationTest extends SapphireTest
{
    protected $usesDatabase = true;

    protected $usesTransactions = false;

    /** @var FaqCategory */
    private $category;

    /** @var array question name => ID */
    private $ids = [];

    /** @var string */
    private $table;

    protected function setUp(): void
    {
        parent::setUp();
        # The migration reports what it copied; keep that out of the test output.
        DB::quiet();

        $this->table = FaqCategory::getSchema()->manyManyComponent(FaqCategory::class, 'Faqs')['join'];
        $this->category = FaqCategory::create(['Title' => 'Returns']);
        $this->category->write();
        # Own SortOrder A=1, B=2, C=3 (creation order); join FaqSortOrder 0, as dev/build leaves
        # a newly added column on existing rows.
        foreach (['A', 'B', 'C'] as $name) {
            $question = FaqQuestion::create(['Question' => $name, 'Answer' => "<p>$name</p>"]);
            $question->write();
            $this->ids[$name] = $question->ID;
            $this->category->Faqs()->add($question);
        }
    }

    public function testDevBuildKeepsTheOldColumnAndTheMigrationCopiesIt(): void
    {
        $this->addOldColumn(['C' => 1, 'A' => 2, 'B' => 3]);

        # The schema half of dev/build: the old column is neither dropped nor renamed to
        # _obsolete_SortOrder, so its values are still there for requireDefaultRecords().
        DB::get_schema()->schemaUpdate(function () {
            FaqCategory::singleton()->requireTable();
        });
        $columns = DB::field_list($this->table);
        $this->assertArrayHasKey('SortOrder', $columns, 'dev/build keeps the old join column');
        $this->assertArrayHasKey('FaqSortOrder', $columns);

        FaqCategory::singleton()->requireDefaultRecords();

        $this->assertSame(['A' => 2, 'B' => 3, 'C' => 1], $this->values('FaqSortOrder'));
        # The relation, the GridField and the page all read FaqSortOrder now.
        $this->assertSame(['C', 'A', 'B'], $this->category->Faqs()->sort('FaqSortOrder')->column('Question'));
        $page = FAQPage::create(['Title' => 'FAQ', 'URLSegment' => 'faq-migrated']);
        $page->write();
        $page->FaqCategories()->add($this->category);
        $this->assertSame(['C', 'A', 'B'], $page->getCategoriesWithFaqs()->first()->Faqs->column('Question'));
    }

    /**
     * Every dev/build runs the migration again; a row dragged since then must keep its new order.
     */
    public function testTheCopyHappensOnce(): void
    {
        $this->addOldColumn(['C' => 1, 'A' => 2, 'B' => 3]);
        $this->assertSame(3, FaqCategory::singleton()->migrateJoinSortOrder());

        # Dragged back into A, B, C after the upgrade.
        $this->setValues('FaqSortOrder', ['A' => 1, 'B' => 2, 'C' => 3]);

        $this->assertSame(0, FaqCategory::singleton()->migrateJoinSortOrder());
        FaqCategory::singleton()->requireDefaultRecords();
        $this->assertSame(['A' => 1, 'B' => 2, 'C' => 3], $this->values('FaqSortOrder'));
    }

    /**
     * Only rows whose FaqSortOrder is still 0 are filled, and only from an old value that is not
     * 0 itself.
     */
    public function testOnlyEmptyRowsAreFilled(): void
    {
        # A: never dragged before the upgrade (old 0). B: dragged before (old 7). C: already has a
        # new value (5), which wins over its old one (9).
        $this->addOldColumn(['A' => 0, 'B' => 7, 'C' => 9]);
        $this->setValues('FaqSortOrder', ['C' => 5]);

        $this->assertSame(1, FaqCategory::singleton()->migrateJoinSortOrder());
        $this->assertSame(['A' => 0, 'B' => 7, 'C' => 5], $this->values('FaqSortOrder'));
    }

    /**
     * A fresh install has no old column: nothing to copy, and no error.
     */
    public function testNothingHappensWithoutTheOldColumn(): void
    {
        $this->assertArrayNotHasKey('SortOrder', DB::field_list($this->table));

        $this->assertSame(0, FaqCategory::singleton()->migrateJoinSortOrder());
        FaqCategory::singleton()->requireDefaultRecords();
        $this->assertSame(['A' => 0, 'B' => 0, 'C' => 0], $this->values('FaqSortOrder'));
    }

    /**
     * Recreate the 1.2.0 join column with the given values, as an install upgrading from it has.
     */
    private function addOldColumn(array $values): void
    {
        DB::query(sprintf('ALTER TABLE "%s" ADD "SortOrder" INT NOT NULL DEFAULT 0', $this->table));
        $this->setValues('SortOrder', $values);
    }

    private function setValues(string $column, array $values): void
    {
        foreach ($values as $name => $value) {
            DB::prepared_query(
                sprintf('UPDATE "%s" SET "%s" = ? WHERE "FaqCategoryID" = ? AND "FaqQuestionID" = ?', $this->table, $column),
                [$value, $this->category->ID, $this->ids[$name]]
            );
        }
    }

    /**
     * The given join column per question name.
     */
    private function values(string $column): array
    {
        $map = DB::prepared_query(
            sprintf('SELECT "FaqQuestionID", "%s" FROM "%s" WHERE "FaqCategoryID" = ?', $column, $this->table),
            [$this->category->ID]
        )->map();
        $byName = [];
        foreach ($this->ids as $name => $id) {
            $byName[$name] = (int) $map[$id];
        }
        return $byName;
    }
}
