# Restruct FAQ Module

A SilverStripe 5 module for managing and displaying frequently asked questions (FAQs).

## Features

- **FAQ Categories**: Organize FAQs into categories
- **FAQs**: Manage questions and answers with HTML formatting
- **FAQ Pages**: Display selected categories and their FAQs on a page
- **Sort Order**: Automatic and manual sorting of FAQs within categories
- **CMS Management**: Dedicated ModelAdmin section for managing FAQs and categories

## Installation

After adding the module:

```bash
composer dump-autoload
vendor/bin/sake dev/build flush=1
```

## Usage

### In the CMS

1. **Managing FAQs**: Go to "FAQs" in the main menu
   - First create categories under the "Faq Category" tab
   - Then add FAQs under the "Faq Question" tab
   - Assign each FAQ to a category

2. **Creating an FAQ Page**:
   - Create a new page of type "FAQ Page"
   - Go to the "FAQCategories" tab
   - Select which categories you want to display on this page
   - Categories are displayed in the order they were added

## Template Customization

The default FAQ page template (`templates/Restruct/FAQ/Pages/Layout/FAQPage.ss`) uses **Bootstrap 5 accordion** components to display FAQs in an expandable/collapsible format.

### Requirements

- Bootstrap 5.x CSS and JavaScript must be included in your theme
- The template uses Bootstrap classes: `accordion`, `accordion-item`, `accordion-header`, `accordion-button`, `accordion-collapse`, `accordion-body`

### Customizing the Template

You can customize the FAQ display in two ways:

1. **Override the template**: Create your own template in your theme directory:
   ```
   themes/your-theme/templates/Restruct/FAQ/Pages/Layout/FAQPage.ss
   ```

2. **Use a different CSS framework**: Copy the template and replace Bootstrap accordion markup with your preferred implementation (e.g., custom JavaScript, Tailwind CSS, etc.)

### Template Structure

The template provides two main methods:
- `$CategoriesWithFaqs`: Returns categories with their associated FAQs grouped together
- `$AllFaqs`: Returns all FAQs for the selected categories (ungrouped)

Refer to the default template for implementation examples.
