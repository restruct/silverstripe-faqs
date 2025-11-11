<?php

namespace Restruct\FAQ\PageControllers;

use PageController;

class FAQPageController extends PageController
{
    /**
     * @var array
     * @config
     */
    private static $allowed_actions = [];

    /**
     * Initialize the controller
     */
    public function init()
    {
        parent::init();
    }
}