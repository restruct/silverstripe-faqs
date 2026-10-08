<?php

namespace Restruct\FAQ\Controllers;

use Restruct\FAQ\Model\FaqQuestion;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\Queries\SQLSelect;
use SilverStripe\ORM\Queries\SQLUpdate;
use SilverStripe\Security\SecurityToken;
use SilverStripe\Versioned\Versioned;

/**
 * API Controller for FAQ tracking
 * Provides a dedicated endpoint for FAQ view tracking that works on any page
 */
class FaqApiController extends Controller
{
    /**
     * @var array
     * @config
     */
    private static $allowed_actions = [
        'incrementView',
    ];

    /**
     * @var string
     * @config
     */
    private static $url_segment = 'faq-api';

    /**
     * AJAX endpoint to increment FAQ view count
     * Secured with CSRF token and session-based tracking to prevent abuse
     *
     * @param HTTPRequest $request
     * @return HTTPResponse
     */
    public function incrementView(HTTPRequest $request)
    {
        // Only allow POST requests
        if (!$request->isPOST()) {
            return $this->jsonResponse(['error' => 'Method not allowed'], 405);
        }

        // Validate CSRF token
        $token = SecurityToken::inst();
        if (!$token->checkRequest($request)) {
            return $this->jsonResponse(['error' => 'Invalid security token'], 403);
        }

        // Get FAQ ID from request
        $faqId = (int) $request->postVar('faqId');
        if (!$faqId) {
            return $this->jsonResponse(['error' => 'Invalid FAQ ID'], 400);
        }

        // Check if FAQ exists
        $faq = FaqQuestion::get()->byID($faqId);
        if (!$faq) {
            return $this->jsonResponse(['error' => 'FAQ not found'], 404);
        }

        // Check session to prevent duplicate counts in the same session
        $session = $request->getSession();
        $viewedFaqs = $session->get('ViewedFaqs') ?: [];

        if (in_array($faqId, $viewedFaqs)) {
            // Already counted in this session
            return $this->jsonResponse([
                'success' => true,
                'viewCount' => $faq->ViewCount,
                'alreadyCounted' => true,
            ]);
        }

        // Increment view count
        # Read-add-write lost increments when two views of one question overlapped (both read the
        # same value), and write() bumped LastEdited on every view (#3):
        //$faq->ViewCount = $faq->ViewCount ? $faq->ViewCount + 1 : 1;
        //$faq->write();
        # Extension point, called once the question is known and before this view is counted.
        $this->extend('onBeforeIncrementView', $faq);

        # One atomic UPDATE instead: the database adds one to whatever value it holds at that
        # moment, and no write() means LastEdited and the write hooks stay untouched. COALESCE keeps
        # the old code's "empty counts as 0": NULL + 1 would stay NULL.
        $tables = $this->viewCountTables();
        foreach ($tables as $table) {
            # A question that was never published has no Live row: that UPDATE matches nothing.
            SQLUpdate::create($table, ['"ViewCount"' => ['COALESCE("ViewCount", 0) + 1' => []]], ['"ID"' => $faqId])
                ->execute();
        }
        # Re-read for the response: the in-memory $faq still holds the value from before the update.
        # From the stage this request reads (Live on the front end of a versioned site).
        $readTable = (count($tables) > 1 && Versioned::get_stage() === Versioned::LIVE) ? $tables[1] : $tables[0];
        $faq->ViewCount = (int) SQLSelect::create('"ViewCount"', $readTable, ['"ID"' => $faqId])
            ->execute()
            ->value();

        // Mark as viewed in session
        $viewedFaqs[] = $faqId;
        $session->set('ViewedFaqs', $viewedFaqs);

        return $this->jsonResponse([
            'success' => true,
            'viewCount' => $faq->ViewCount,
            'alreadyCounted' => false,
        ]);
    }

    /**
     * The tables (ANSI quoted) holding a question's ViewCount: the base table, and on a site that
     * adds Versioned with stages to FaqQuestion also its Live table, which the front end reads.
     * The raw UPDATE bypasses Versioned, so without the Live table the counts visitors' pages
     * show would never move.
     *
     * @return string[] base table first
     */
    protected function viewCountTables()
    {
        $table = DataObject::getSchema()->tableForField(FaqQuestion::class, 'ViewCount');
        $tables = [sprintf('"%s"', $table)];

        $question = FaqQuestion::singleton();
        if ($question->hasExtension(Versioned::class) && $question->hasStages()) {
            $tables[] = sprintf('"%s"', $question->stageTable($table, Versioned::LIVE));
        }

        return $tables;
    }

    /**
     * Helper method to return JSON responses
     *
     * @param array $data
     * @param int $statusCode
     * @return HTTPResponse
     */
    private function jsonResponse(array $data, int $statusCode = 200)
    {
        $response = HTTPResponse::create();
        $response->addHeader('Content-Type', 'application/json');
        $response->setStatusCode($statusCode);
        $response->setBody(json_encode($data));
        return $response;
    }
}