# Changelog

## 1.1.0 (unreleased)

Silverstripe 6 support, on the same line as Silverstripe 5. No breaking changes: a 1.0.x
installation on Silverstripe 5 can update in place.

The repository had no open or closed GitHub issues or pull requests at the time of this release.

### Added

- **Silverstripe 6 support.** `composer.json` now requires `silverstripe/cms: ^5 || ^6` and
  `symbiote/silverstripe-gridfieldextensions: ^4 || ^5`.
- A test suite (`tests/`) covering the schema, the CMS fields, the FAQs admin, both template data
  methods, the rendered template and the view-counting endpoint, and CI running it on
  Silverstripe 5 (PHP 8.1, 8.3) and Silverstripe 6 (PHP 8.3, 8.4), plus a real `dev/build` /
  `db:build` and, on 6, `config:audit`.
- `composer.json` declares `php: ^8.1` (it declared none before), `license: MIT` (matching the
  LICENSE file already in the repository) and a `funding` link.
- `CLASS_DESCRIPTION` translation keys for the page type (en, nl), which Silverstripe 5.4 and 6
  look up. The older `DESCRIPTION` keys stay for 5.0-5.3.

### Fixed

- **On Silverstripe 6, an FAQ page failed to render.** `getCategoriesWithFaqs()` used
  `SilverStripe\ORM\ArrayList` and `SilverStripe\View\ArrayData`, which Silverstripe 6 moved to
  `SilverStripe\Model\...` with no alias. The class is now picked per major.
- **On Silverstripe 6, the FAQ page type lost its description and icon.** Silverstripe 6 reads
  `class_description` and `cms_icon_class` only; the page type declared the Silverstripe 5 names
  `description` and `icon_class`. Both names are now declared.
- **Questions within a category ignored the order dragged in the CMS.** The category's "FAQ
  Questions" GridField stores its drag order on the category-question relation, but
  `getCategoriesWithFaqs()` sorted by each question's own `SortOrder`, so dragging changed the CMS
  list and not the page. The relation's order now leads, and the question's own order breaks ties -
  so **a category nobody has drag-sorted keeps exactly the order it had**, while one that was
  drag-sorted now shows that order on the page for the first time.
- **`getAllFaqs()` listed a question once per selected category it was in**, so a question in two
  of the page's categories appeared twice. Each question is now listed once. It also returns an
  empty list, instead of throwing, when the selected categories contain no questions.
- The `FAQPage` class lived in `src/Pages/FaqPage.php`. Silverstripe's own class manifest found
  it, but Composer's PSR-4 autoloader could not on a case-sensitive filesystem. The file is now
  `src/Pages/FAQPage.php`.

### Documentation

- README: requirements and compatibility table, installation for both majors, ordering rules,
  the view-counting endpoint and its responses, every config option with its default, and how to
  run the tests. Removed the claim that categories show "in the order they were added" (they
  have always been drag-sortable) and the `composer dump-autoload` installation step.

## 1.0.0 - 1.0.5 (November 2025)

Silverstripe 5 rewrite of the module: FAQ categories and questions managed in a ModelAdmin, an
FAQ page type with a vanilla-JS accordion, and per-question view counting.
