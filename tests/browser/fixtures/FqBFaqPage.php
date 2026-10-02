<?php

namespace Restruct\FqBrowser;

use Restruct\FAQ\Model\FaqCategory;
use Restruct\FAQ\Model\FaqQuestion;
use Restruct\FAQ\Pages\FAQPage;

/**
 * BROWSER-TEST FIXTURE ONLY - an FAQPage that the bare scratch host can render (see
 * FqBFaqPageController), plus the seed data the specs read.
 *
 * Never loaded by a real install: it lives under tests/browser/, which carries a _manifest_exclude
 * marker, and the browser-test runner copies it into a scratch host's app/ before dev/build.
 * It adds no fields or behaviour to FAQPage; it only exists to swap in a controller.
 *
 * Every dev/build (the runner does one per run) wipes and re-seeds all FAQ data and both pages, so
 * each run starts with the same order and zero view counts.
 */
class FqBFaqPage extends FAQPage
{
    private static $table_name = 'FqBFaqPage';

    private static $controller_name = FqBFaqPageController::class;

    public function requireDefaultRecords()
    {
        parent::requireDefaultRecords();

        # Only once, for this class (requireDefaultRecords() runs per class in the hierarchy).
        if (static::class !== self::class) {
            return;
        }
        foreach (self::get() as $old) {
            $old->doUnpublish();
            $old->delete();
        }
        foreach (FaqQuestion::get() as $old) {
            $old->delete();
        }
        foreach (FaqCategory::get() as $old) {
            $old->delete();
        }

        # Questions are created in this order, so their own SortOrder is 1..8.
        $q = [];
        foreach ([
            'billing-a' => ['How do I pay?', '<p>By <strong>bank transfer</strong>.</p>'],
            'billing-b' => ['Can I get an invoice?', '<p>Yes, from your account page.</p>'],
            'shipping-a' => ['How long does delivery take?', '<p>Two to three days.</p>'],
            'shipping-b' => ['Do you ship abroad?', '<p>Within the EU only.</p>'],
            'shared' => ['How do I contact you?', '<p>Use the contact form.</p>'],
            'returns-a' => ['Can I return an item?', '<p>Within 30 days.</p>'],
            'returns-b' => ['Who pays for the return?', '<p>We do.</p>'],
            'returns-c' => ['When do I get my money back?', '<p>Within a week.</p>'],
        ] as $key => [$question, $answer]) {
            $q[$key] = FaqQuestion::create(['Question' => $question, 'Answer' => $answer]);
            $q[$key]->write();
        }

        # Category "Billing": dragged so that the invoice question comes FIRST (join SortOrder),
        # against the questions' own order. "Shipping": never dragged (join SortOrder all 0), so the
        # questions' own order decides. "Shared" is in both categories.
        $billing = FaqCategory::create(['Title' => 'Billing']);
        $billing->write();
        $billing->Faqs()->add($q['billing-b'], ['SortOrder' => 1]);
        $billing->Faqs()->add($q['billing-a'], ['SortOrder' => 2]);
        $billing->Faqs()->add($q['shared'], ['SortOrder' => 3]);

        $shipping = FaqCategory::create(['Title' => 'Shipping']);
        $shipping->write();
        $shipping->Faqs()->add($q['shipping-b']);
        $shipping->Faqs()->add($q['shipping-a']);
        $shipping->Faqs()->add($q['shared']);

        # For the admin drag spec, which reorders it (so the order specs never depend on it).
        $returns = FaqCategory::create(['Title' => 'Returns']);
        $returns->write();
        $returns->Faqs()->add($q['returns-a'], ['SortOrder' => 1]);
        $returns->Faqs()->add($q['returns-b'], ['SortOrder' => 2]);
        $returns->Faqs()->add($q['returns-c'], ['SortOrder' => 3]);

        # Not on any page.
        FaqCategory::create(['Title' => 'Unused'])->write();

        # The FAQ page shows Shipping, Billing, Returns (page join SortOrder), against the Title sort.
        $page = self::create(['Title' => 'FAQ test', 'URLSegment' => 'faq-test', 'Content' => '<p class="fqb-intro">Questions and answers.</p>']);
        $page->write();
        $page->FaqCategories()->add($shipping, ['SortOrder' => 1]);
        $page->FaqCategories()->add($billing, ['SortOrder' => 2]);
        $page->FaqCategories()->add($returns, ['SortOrder' => 3]);
        $page->publishRecursive();

        # A page without categories shows the "no FAQs" message.
        $empty = self::create(['Title' => 'FAQ empty', 'URLSegment' => 'faq-empty']);
        $empty->write();
        $empty->publishRecursive();
    }
}
