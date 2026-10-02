<?php

namespace Restruct\FqBrowser;

use Restruct\FAQ\PageControllers\FAQPageController;
use Restruct\FAQ\Pages\FAQPage;
use SilverStripe\View\Requirements;
use SilverStripe\View\SSViewer;

/**
 * BROWSER-TEST FIXTURE ONLY - renders the module's own Layout template
 * (templates/Restruct/FAQ/Pages/Layout/FAQPage.ss) inside a minimal main template.
 *
 * The scratch host is a bare recipe-cms project without a theme, so there is no main Page.ss to
 * carry $Layout, and a fixture cannot ship one: the runner copies tests/browser/fixtures/ into
 * app/src/BrowserFixtures/, where the template manifest does not look. So the main template is a
 * string here. Everything else (init() and its Requirements, the Layout template, FAQPage's data
 * methods) is the module's own code.
 */
class FqBFaqPageController extends FAQPageController
{
    private const MAIN = <<<'SS'
<!DOCTYPE html>
<html lang="en">
<head>
<% base_tag %>
$MetaTags
</head>
<body>
<main id="fqb-main">
<h1>$Title</h1>
$Layout
</main>
</body>
</html>
SS;

    public function index()
    {
        # The module's Layout template, looked up the way a theme's main template's $Layout would.
        $layout = $this->renderWith([['type' => 'Layout', FAQPage::class]]);
        $model = $this->customise(['Layout' => $layout]);
        if (class_exists(\SilverStripe\TemplateEngine\SSTemplateEngine::class)) {
            # Silverstripe 6
            $html = (new \SilverStripe\TemplateEngine\SSTemplateEngine())
                ->renderString(self::MAIN, new \SilverStripe\View\ViewLayerData($model));
        } else {
            # Silverstripe 5
            $html = SSViewer::fromString(self::MAIN)->process($model);
        }
        # Neither string renderer adds the Requirements (only SSViewer::process() of a file template
        # does), so add them here: the CSS and JS from FAQPageController::init().
        return Requirements::includeInHTML((string) $html);
    }
}
