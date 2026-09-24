<?php

namespace Restruct\FAQ\Tests;

use Restruct\FAQ\Model\FaqCategory;
use Restruct\FAQ\Model\FaqQuestion;
use Restruct\FAQ\PageControllers\FAQPageController;
use Restruct\FAQ\Pages\FAQPage;
use SilverStripe\CMS\Controllers\CMSMain;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\ORM\DB;
use SilverStripe\View\Requirements;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;

/**
 * The page type: its schema, CMS field, page-type metadata, the two template data methods, and
 * the rendered template.
 */
class FAQPageTest extends SapphireTest
{
    protected static $fixture_file = 'FaqTest.yml';

    public function testSchemaIsBuilt(): void
    {
        $schema = DB::get_schema();

        $this->assertTrue($schema->hasTable('FAQPage_FaqCategories'));
        $join = $schema->fieldList('FAQPage_FaqCategories');
        foreach (['FAQPageID', 'FaqCategoryID', 'SortOrder'] as $field) {
            $this->assertArrayHasKey($field, $join, "FAQPage_FaqCategories.$field");
        }
    }

    public function testCmsFieldOrdersCategoriesByTheJoinSortOrder(): void
    {
        $fields = $this->objFromFixture(FAQPage::class, 'page')->getCMSFields();

        $grid = $fields->fieldByName('Root.FAQCategories.FaqCategories');
        $this->assertInstanceOf(GridField::class, $grid);
        $this->assertSame(FaqCategory::class, $grid->getModelClass());
        $this->assertSame('FAQ Categories', $grid->Title());

        $orderable = $grid->getConfig()->getComponentByType(GridFieldOrderableRows::class);
        $this->assertNotNull($orderable, 'Categories on a page are drag-sortable');
        $this->assertSame('SortOrder', $orderable->getSortField());
    }

    /**
     * The page-type description shown in the "add page" dialog. Silverstripe 6 reads only
     * class_description (SiteTree::$description was renamed), so a page type declaring only the
     * old name silently loses its description there.
     */
    public function testPageTypeDescription(): void
    {
        $this->assertSame(
            'A page that displays frequently asked questions organized by categories',
            FAQPage::singleton()->classDescription()
        );
    }

    /**
     * The site-tree icon. Silverstripe 6 reads only cms_icon_class (renamed from icon_class), so
     * a page type declaring only the old name silently falls back to the generic page icon.
     */
    public function testPageTypeIcon(): void
    {
        if (method_exists(CMSMain::class, 'getRecordIconCssClass')) {
            // Silverstripe 6
            $icon = CMSMain::singleton()->getRecordIconCssClass(FAQPage::class);
        } else {
            // Silverstripe 5
            $icon = FAQPage::singleton()->getIconClass();
        }

        $this->assertSame('font-icon-help-circled', $icon);
    }

    public function testAllFaqsIsNullWithoutCategories(): void
    {
        $this->assertNull($this->objFromFixture(FAQPage::class, 'page_without_categories')->getAllFaqs());
    }

    public function testAllFaqsListsEachQuestionOfTheSelectedCategoriesOnce(): void
    {
        $faqs = $this->objFromFixture(FAQPage::class, 'page')->getAllFaqs();

        // q_returns is in two selected categories; q_orphan and q_elsewhere are in none of them.
        // Ordered by the questions' own SortOrder: returns 10, payment 20, shipping 30.
        $this->assertSame(
            ['Can I return an item?', 'Which payment methods do you accept?', 'How long does shipping take?'],
            $faqs->column('Question')
        );
    }

    public function testAllFaqsIsAnEmptyListWhenTheSelectedCategoriesHaveNoQuestions(): void
    {
        $faqs = $this->objFromFixture(FAQPage::class, 'page_with_only_an_empty_category')->getAllFaqs();

        $this->assertNotNull($faqs);
        $this->assertCount(0, $faqs);
    }

    /**
     * Regression: a question in two of the page's categories was listed twice by getAllFaqs().
     */
    public function testAllFaqsDoesNotDuplicateAQuestionInTwoCategories(): void
    {
        $questions = $this->objFromFixture(FAQPage::class, 'page')->getAllFaqs()->column('Question');

        $this->assertSame(array_unique($questions), $questions);
        $this->assertCount(3, $questions);
    }

    /**
     * Regression: getCategoriesWithFaqs() sorted each category's questions by the question's
     * own SortOrder, so dragging questions in the category's GridField (which writes the join
     * table's SortOrder) never changed the order on the page.
     */
    public function testCategoriesWithFaqsHonoursTheOrderDraggedInTheCategory(): void
    {
        $orders = $this->objFromFixture(FAQPage::class, 'page')->getCategoriesWithFaqs()->first();

        // Dragged order: shipping (join 1), returns (join 2). Own order: returns 10, shipping 30.
        $this->assertSame(
            ['How long does shipping take?', 'Can I return an item?'],
            $orders->Faqs->column('Question')
        );
    }

    public function testCategoriesWithFaqsFallsBackToTheQuestionOrderWhenNothingWasDragged(): void
    {
        $item = $this->objFromFixture(FAQPage::class, 'page_never_dragged')->getCategoriesWithFaqs()->first();

        // Join SortOrder is 0 for both, so the questions' own SortOrder decides, as before the fix.
        $this->assertSame(
            ['How long does shipping take?', 'A question only in a category the page does not show'],
            $item->Faqs->column('Question')
        );
    }

    public function testCategoriesWithFaqsIsEmptyWithoutCategories(): void
    {
        $result = $this->objFromFixture(FAQPage::class, 'page_without_categories')->getCategoriesWithFaqs();

        $this->assertCount(0, $result);
    }

    public function testCategoriesWithFaqsFollowsBothJoinSortOrders(): void
    {
        $result = $this->objFromFixture(FAQPage::class, 'page')->getCategoriesWithFaqs();

        // Page order is orders (1), money (2), empty (3). The empty category is skipped rather
        // than rendered as a heading with nothing under it.
        $this->assertSame(
            ['Zulu orders', 'Alpha money'],
            array_map(fn ($item) => $item->Category->Title, $result->toArray())
        );

        [$orders, $money] = $result->toArray();

        // Within a category the relation's SortOrder wins over the questions' own SortOrder
        // (shipping is 30 and returns is 10 on the question, 1 and 2 in the category).
        $this->assertSame(
            ['How long does shipping take?', 'Can I return an item?'],
            $orders->Faqs->column('Question')
        );
        $this->assertSame(
            ['Which payment methods do you accept?', 'Can I return an item?'],
            $money->Faqs->column('Question')
        );
        $this->assertInstanceOf(FaqCategory::class, $orders->Category);
    }

    public function testControllerRequiresTheAccordionAssets(): void
    {
        Requirements::clear();
        $page = $this->objFromFixture(FAQPage::class, 'page');
        $controller = FAQPageController::create($page);
        $controller->doInit();

        $js = implode(' ', array_keys(Requirements::backend()->getJavascript()));
        $css = implode(' ', array_keys(Requirements::backend()->getCSS()));

        $this->assertStringContainsString('client/src/js/faq-accordion.js', $js);
        $this->assertStringContainsString('client/src/js/faq-view-tracker.js', $js);
        $this->assertStringContainsString('client/src/css/faq-accordion.css', $css);
    }

    public function testLayoutTemplateRendersCategoriesAndQuestions(): void
    {
        $page = $this->objFromFixture(FAQPage::class, 'page');
        $orders = $this->objFromFixture(FaqCategory::class, 'cat_orders');
        $shipping = $this->objFromFixture(FaqQuestion::class, 'q_shipping');

        $html = (string) FAQPageController::create($page)->renderWith(['type' => 'Layout', FAQPage::class]);

        $this->assertStringContainsString('<h2 class="faq-category__title">Zulu orders</h2>', $html);
        $this->assertStringContainsString('<h2 class="faq-category__title">Alpha money</h2>', $html);
        $this->assertStringNotContainsString('Empty category', $html);
        $this->assertStringNotContainsString('A question only in a category', $html);
        $this->assertStringContainsString('<p>Three to five working days.</p>', $html);
        $this->assertStringContainsString('data-faq-id="' . $shipping->ID . '"', $html);

        // $Up.Category.ID inside the nested loop: the answer id carries the CATEGORY's ID, so a
        // question listed in two categories still gets two distinct ids. q_returns is in both
        // categories. The guards below keep the assertions able to fail: with a category ID equal
        // to the question ID, "{$Up.Category.ID}-{$ID}" and "{$ID}-{$ID}" render the same.
        $money = $this->objFromFixture(FaqCategory::class, 'cat_money');
        $returns = $this->objFromFixture(FaqQuestion::class, 'q_returns');
        $this->assertNotEquals($orders->ID, $returns->ID);
        $this->assertNotEquals($orders->ID, $money->ID);
        foreach ([$orders, $money] as $category) {
            $this->assertStringContainsString(
                'id="faq-answer-' . $category->ID . '-' . $returns->ID . '"',
                $html
            );
            $this->assertStringContainsString(
                'aria-controls="faq-answer-' . $category->ID . '-' . $returns->ID . '"',
                $html
            );
        }

        // Categories render in the page's order, not by Title.
        $this->assertLessThan(strpos($html, 'Alpha money'), strpos($html, 'Zulu orders'));
    }

    public function testLayoutTemplateShowsTheEmptyMessageWithoutCategories(): void
    {
        $page = $this->objFromFixture(FAQPage::class, 'page_without_categories');

        $html = (string) FAQPageController::create($page)->renderWith(['type' => 'Layout', FAQPage::class]);

        $this->assertStringContainsString('No FAQs have been added to this page yet.', $html);
    }
}
