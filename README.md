# Restruct FAQ Module

*Maintained by [Restruct](https://github.com/restruct). If this module saves you time, you can
[support ongoing maintenance](https://github.com/sponsors/restruct).*

A Silverstripe module for managing and displaying frequently asked questions (FAQs).

## Features

- **FAQ Categories**: Organize FAQs into categories
- **FAQs**: Manage questions and answers with HTML formatting
- **FAQ Pages**: Display selected categories and their FAQs on a page
- **Sort Order**: Automatic and manual sorting of FAQs within categories
- **CMS Management**: Dedicated ModelAdmin section for managing FAQs and categories
- **View counting**: Counts how often each question is opened on the site, once per visitor session

## Requirements

| Branch | Module version | Silverstripe | PHP |
| ------ | -------------- | ------------ | --- |
| `main` | `1.1.x` | `^5 \|\| ^6` | `^8.1` (Silverstripe 6 itself needs 8.3+) |
| (tags only) | `1.0.x` | `^5` | not declared (Silverstripe 5 needs 8.1+) |

`composer.json` is the source of truth. Silverstripe 5 is supported until its end of life in April
2027; Silverstripe 4 is not supported.

Dependencies: `silverstripe/cms` and `symbiote/silverstripe-gridfieldextensions` (`^4` on
Silverstripe 5, `^5` on Silverstripe 6), for the drag-sortable GridFields.

**Silverstripe 6:** TinyMCE is a separate module there and `silverstripe/recipe-cms` does not
include it. Without `silverstripe/htmleditor-tinymce` installed, the Answer field is not a rich-text
editor. Most Silverstripe 6 projects already require it; the module lists it under `suggest`.

## Installation

```bash
composer require restruct/silverstripe-faq
```

Then build the database:

```bash
vendor/bin/sake dev/build flush=1     # Silverstripe 5
vendor/bin/sake db:build --flush      # Silverstripe 6
```

The package is `restruct/silverstripe-faq` (singular); the repository is `silverstripe-faqs`.

`FAQPage` extends your project's `Page` class and `FAQPageController` extends `PageController`,
as with any page-type module, so the project must define both (every standard Silverstripe
project does).

## Usage

### In the CMS

1. **Managing FAQs**: Go to "FAQs" in the main menu
   - First create categories under the "Faq Category" tab
   - Then add FAQs under the "Faq Question" tab
   - Assign each FAQ to a category (a question can be in several)
   - Within a category, drag the questions into the order they should appear on the page

2. **Creating an FAQ Page**:
   - Create a new page of type "FAQ Page"
   - Go to the "FAQ Categories" tab
   - Select which categories you want to display on this page
   - Drag the categories into the order they should appear on the page

### Ordering

- **Categories on a page** follow the order dragged on the page's "FAQ Categories" tab.
- **Questions within a category** follow the order dragged in that category's "FAQ Questions"
  tab. Where nothing has been dragged yet, the questions' own sort order (the order they were
  created in) decides.
- **`$AllFaqs`** has no single category to take an order from, so it uses the questions' own
  sort order.

## Template Customization

The default FAQ page template (`templates/Restruct/FAQ/Pages/Layout/FAQPage.ss`) uses a **custom accordion** implementation with vanilla JavaScript and CSS - no external dependencies required.

### Client Assets

The module includes the following client-side files:

1. **faq-accordion.js**: Core accordion functionality
   - Handles expand/collapse behavior
   - Dispatches `faq:opened` custom events for tracking
   - Keyboard navigation support

2. **faq-view-tracker.js**: Analytics tracking
   - Tracks when users open FAQ items
   - CSRF token protection
   - Session-based deduplication

3. **faq-accordion.css**: Accordion styling
   - Clean, modern design
   - Smooth transitions
   - Mobile responsive

`FAQPageController::init()` requires all three on every FAQ page.

### Customizing the Template

You can customize the FAQ display in several ways:

1. **Override the template**: Create your own template in your theme directory:
   ```
   themes/your-theme/templates/Restruct/FAQ/Pages/Layout/FAQPage.ss
   ```

2. **Override the styles**: Override CSS variables or classes in your theme's stylesheet

3. **Extend functionality**: Listen to the `faq:opened` custom event to add your own behavior:
   ```javascript
   document.addEventListener('faq:opened', function(event) {
       console.log('FAQ opened:', event.detail.faqId);
   });
   ```

### Template Structure

The template provides two main methods:
- `$CategoriesWithFaqs`: Returns categories with their associated FAQs grouped together. Each
  item has `$Category` (the `FaqCategory`) and `$Faqs` (its questions); categories without
  questions are left out.
- `$AllFaqs`: Returns all FAQs for the selected categories (ungrouped), each question once even
  when it is in several of them. It is `null` when the page has no categories selected.

Refer to the default template for implementation examples.

To keep view counting working in your own template, render each question's toggle with
`data-faq-id="{$ID}"` and `data-security-token="{$ViewToken}"`, as the default template does.

## View counting

`faq-view-tracker.js` posts to `faq-api/incrementView` (routed to `FaqApiController` in
`_config/routes.yml`) when a question is opened. The endpoint accepts only `POST` with a valid
`SecurityID` token, counts a question at most once per session, and answers JSON:

| Response | When |
| -------- | ---- |
| `405` | not a `POST` |
| `403` | missing or wrong security token |
| `400` | no `faqId` |
| `404` | no question with that ID |
| `200` `{"success":true,"viewCount":n,"alreadyCounted":false}` | counted |
| `200` `{"success":true,"viewCount":n,"alreadyCounted":true}` | already counted in this session |

The count is shown in the "FAQs" admin list (`ViewCount`) and is not editable in the CMS.

The script posts to the absolute path `/faq-api/incrementView`, so view counting assumes the site
runs at the domain root.

## Configuration

All options are standard Silverstripe `private static` config, set in YAML:

| Class | Option | Default |
| ----- | ------ | ------- |
| `Restruct\FAQ\Admin\FAQAdmin` | `url_segment` | `faq` |
| | `menu_title` | `FAQs` |
| | `menu_icon_class` | `font-icon-help-circled` |
| | `menu_priority` | `3` |
| `Restruct\FAQ\Pages\FAQPage` | `class_description` (and `description` on Silverstripe 5 before 5.4) | `A page that displays frequently asked questions organized by categories` |
| | `cms_icon_class` (Silverstripe 6) / `icon_class` (Silverstripe 5) | `font-icon-help-circled` |

The FAQs admin removes the export, print and import buttons from both of its GridFields.

## Running the tests

The module cannot be tested on its own: it needs a host Silverstripe project with a `Page` and
`PageController`. Require it there through a Composer **path repository with `symlink: true`** -
`/tests` is `export-ignore`, so a dist or mirrored install contains no tests - and add its test
namespace to the host's `autoload-dev`:

```json
"autoload-dev": {
    "psr-4": {
        "Restruct\\FAQ\\Tests\\": "vendor/restruct/silverstripe-faq/tests/"
    }
}
```

Then:

```bash
# Silverstripe 5 (PHPUnit 9) - the path must come before flush=1
vendor/bin/phpunit vendor/restruct/silverstripe-faq/tests flush=1

# Silverstripe 6 (PHPUnit 11) - a flush=1 argument is ignored, use the env var
SS_PHPUNIT_FLUSH=1 vendor/bin/phpunit vendor/restruct/silverstripe-faq/tests
```

CI runs the same suite against Silverstripe 5 and 6 on every push; see `.github/workflows/ci.yml`.

## Licence

MIT, see [LICENSE](LICENSE). Changes per release: [CHANGELOG.md](CHANGELOG.md).
