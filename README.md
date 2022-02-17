# Silverstripe Frequently Asked Questions module

## TODO/Desired functionality:
- [ ] For SEO purposes I'd like to move the FAQ from DataObject to a SiteTree subclass (so each FAQ is an actual page with URL).
- [ ] Categorization & Tagging should be removed from this module and provided by new dependency: [Filterable Archive module](https://github.com/restruct/silverstripe-filterablearchive)
- [ ] FaqAdmin should be removed, FAQs will be manageable from (a) FaqPage(s) - FaqPages are 
- [ ] A SiteTree extension provides `faqvote` functionality (see `FaqSiteTreeControllerExtension`); simple background ajax method called to count the times a specific question has been opened (maybe use WriteWithoutVersion or comparable to simply keep track in a field on the FAQ without creating thousands of version rows in the DB)
- [ ] FAQs are/can be auto-sorted based on their 'vote count'/popularity
- [ ] A SiteTree extension provides functionality to link/include a few specific FAQs on a page (eg below the content/in a block if using Elemental)
- [ ] ...
