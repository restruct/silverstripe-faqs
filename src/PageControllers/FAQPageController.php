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

}
