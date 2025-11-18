<?php

namespace Restruct\FAQ\PageControllers;

use PageController;
use Restruct\FAQ\Model\FaqQuestion;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Security\SecurityToken;
use SilverStripe\View\Requirements;

class FAQPageController extends PageController
{
    /**
     * @var array
     * @config
     */
    private static $allowed_actions = [
        'incrementView',
    ];

    /**
     * Initialize the controller
     */
    public function init()
    {
        parent::init();
        // Include FAQ accordion CSS and JavaScript
        Requirements::css('restruct/silverstripe-faq:client/src/css/faq-accordion.css');
        Requirements::javascript('restruct/silverstripe-faq:client/src/js/faq-accordion.js');
        Requirements::javascript('restruct/silverstripe-faq:client/src/js/faq-view-tracker.js');
    }

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
        $faq->ViewCount = $faq->ViewCount ? $faq->ViewCount + 1 : 1;
        $faq->write();

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
