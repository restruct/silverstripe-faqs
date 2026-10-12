# Changelog

## 1.3.0 (Unreleased)

### Fixed

- **Dragging questions within a category was sometimes not saved** (#5). The category's join field
  for the question order was called `SortOrder`, the same name as `FaqQuestion`'s own field. Read
  through the relation, a question then carries its own `SortOrder` under that name instead of the
  category's, and `GridFieldOrderableRows` compared the new positions with those values and skipped
  rows it had to write. A drag that put the questions back into their own (creation) order was not
  saved at all: the reorder request still answered 200, and the grid (on Silverstripe 5) and the
  FAQ page kept the old order.

### Changed

- The join field is now **`FaqSortOrder`** (`FaqCategory::$many_many_extraFields`, table
  `FaqCategory_Faqs`), in the category's "FAQ Questions" GridField and in
  `FAQPage::getCategoriesWithFaqs()`. `FAQPage`'s own join field `FaqCategories.SortOrder` keeps
  its name: `FaqCategory` has no field of that name, so it never clashed.

### Upgrading

- Run `dev/build` (`sake db:build` on Silverstripe 6). `FaqCategory::requireDefaultRecords()`
  copies the old `FaqCategory_Faqs.SortOrder` values into `FaqSortOrder`, only for rows whose
  `FaqSortOrder` is still 0 and whose old value is not, so every category keeps the order dragged
  before the upgrade, and later builds never undo a drag made since.
- `dev/build` leaves the old `FaqCategory_Faqs.SortOrder` column in place (Silverstripe never drops
  a column that is no longer configured). Nothing reads or writes it any more; drop it by hand
  once the build has run, if you like.
- Project code that names the join field must use `FaqSortOrder`: extra fields passed to
  `$category->Faqs()->add($question, ['SortOrder' => ...])` are now silently ignored, and a sort on
  `"FaqCategory_Faqs"."SortOrder"` reads the stale old column.

## 1.2.0 (2026-10-08)

### Added

- `FaqQuestion::getViewTrackingURL()` (`$ViewTrackingURL` in templates): the view-count endpoint
  under the site's base URL. The default template renders it as `data-view-tracking-url` on each
  question's toggle (#2).
- An `onBeforeIncrementView($faq)` extension hook on `FaqApiController`, called once the question
  is known and before a view is counted (#3).

### Fixed

- **View counting did nothing on a site installed in a subdirectory** (#2). The tracker posted to
  the fixed path `/faq-api/incrementView`. It now posts to the toggle's `data-view-tracking-url`;
  a custom template without that attribute falls back to the page's `<base href>`, then to the
  old root path. Custom templates should add the attribute (README, "Template Structure").
- The view tracker no longer writes `FAQ view tracked: ...` to the browser console on every
  counted view (#4). Errors are still reported with `console.error`.
- **Concurrent views of one question could lose counts, and every counted view bumped the
  question's `LastEdited`** (#3). `incrementView` read `ViewCount`, added one in PHP and called
  `write()`. It now increments in a single SQL `UPDATE`, so overlapping views all count, and
  `LastEdited` changes only when the question is edited. Since no `write()` happens any more,
  `onBeforeWrite`/`onAfterWrite` hooks on `FaqQuestion` no longer run when a view is counted.
  On a site that adds `Versioned` with stages to `FaqQuestion`, the Live table is raised as well,
  so the counts visitors' pages read move too.

## 1.1.0 (2026-09-25)

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
  LICENSE file already in the repository) and a `funding` link. The licence itself is unchanged;
  the LICENSE copyright lines now name every holder: Restruct web & apps and the two outside
  contributors whose code the module contains (it read "2025 Restruct web & apps" before).
- `composer.json` suggests `silverstripe/htmleditor-tinymce`, which Silverstripe 6 needs for a
  rich-text Answer field.
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
  run the tests, and every attribute a custom FAQ template must keep for the accordion and view
  counting to work. Removed the claim that categories show "in the order they were added" (they
  have always been drag-sortable) and the `composer dump-autoload` installation step.

## 1.0.0 - 1.0.5 (November 2025)

Silverstripe 5 rewrite of the module: FAQ categories and questions managed in a ModelAdmin, an
FAQ page type with a vanilla-JS accordion, and per-question view counting.
