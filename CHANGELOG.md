# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Nothing yet

### Changed
- Nothing yet

### Fixed
- Nothing yet

## [1.4.1] - 2025-01-07

### Fixed
- **Use Theme Styling option not working** - When enabled, the plugin CSS was still being loaded
  - Plugin stylesheet (`form.css`) is now completely skipped when theme styling is enabled
  - Google Fonts are not loaded when theme styling is enabled
  - No custom inline styles are generated when theme styling is enabled
  - Your theme now has full control over the form appearance

### Added
- Informational note in admin UI when theme styling is enabled explaining that theme controls form appearance

## [1.4.0] - 2025-01-07

### Added
- **Import Form Button on Forms Page** - Quick access to import forms directly from Forms list
- **Submissions Page Enhancements**
  - Filter bar with status, form, and date range filters
  - Export dropdown with three options: Export All, Export Filtered, Export Selected
  - CSV export includes all form field data (not just table columns)
  - Pagination with 20 items per page
  - Checkbox column for bulk selection
  - Display actual form names instead of IDs
  - Failed submissions stat with error status styling
- **Redesigned Submission Detail Modal**
  - Modern gradient header with status badge
  - Three-column metadata section (Date, IP, Form ID)
  - Clean data grid layout for form fields
  - Auto-formatting of field names (snake_case to Title Case)
  - Full-width layout for long text fields
  - CRM response section with code block styling
- **Use Theme Styling Option**
  - Toggle to disable all plugin styles on frontend
  - Let your theme control the form appearance
  - When enabled, styling options are hidden
  - Adds `aicrmform-theme-styled` class for custom CSS targeting
  - Minimal reset styles for theme inheritance

### Changed
- Import Form and Create Form buttons now have matching primary style
- Improved button alignment in page headers
- REST API endpoint for exporting submissions with full data

### Fixed
- Import modal CRM Form ID field not showing on Forms page
- Styling options toggle not hiding options in Edit Form modal

## [1.3.0] - 2025-01-07

### Added
- **Scalable Integration Architecture** - New modular system for form plugin integrations
  - Interface-based design (`AICRMFORM_Form_Integration_Interface`)
  - Abstract base class for common functionality
  - Integration Manager singleton for registration and retrieval
- **Gravity Forms Integration** - Full support for importing Gravity Forms
  - Import forms with field mapping
  - Shortcode interception for seamless replacement
  - Support for all Gravity Forms field types
- **Multiple Plugin Deactivation** - Disable multiple source plugins at once
  - Dialog shows all imported plugins
  - Batch deactivation with proper error handling
- Third-party integration hook (`aicrmform_register_integrations` filter)

### Changed
- Refactored Contact Form 7 integration to use new architecture
- Form Importer now uses Integration Manager
- Form Shortcode handler supports multiple plugins dynamically
- Improved field option normalization for different plugin formats

### Fixed
- Array to string conversion warning when rendering select/checkbox fields
- Shortcode interception not working for some plugin combinations
- Multiple plugin disable dialog only showing last imported plugin

## [1.2.0] - 2025-01-03

### Added
- Form Import module for Contact Form 7
  - Import forms from CF7 with one click
  - Use same shortcode option (no page updates needed)
  - Automatic plugin deactivation prompt after import
  - Hash ID prefix matching for CF7 shortcodes
- Font Family setting with Google Fonts integration
- Font Size setting for forms
- Form Background Color setting
- Auto-remove submissions after X days (configurable in Settings)
- Styling options in Edit Form modal

### Changed
- Settings submenu moved to bottom of AI CRM Forms menu
- Improved Quick Start widget with dynamic progress
- Better CRM mapping warning with "Ignore & Save" option

### Fixed
- Form deletion not removing card from UI immediately
- Total Forms and Active Forms stats not updating after deletion
- CF7 forms breaking when not yet imported
- Stale shortcode mappings causing "Form not found" error
- Import button icon alignment
- Empty state alignment in import modal
- Custom CSS spacing issues

### Security
- Added cleanup for stale shortcode mappings
- Proper nonce verification for import endpoints

## [1.1.1] - 2025-01-03

### Fixed
- Minor bug fixes and improvements

## [1.1.0] - 2025-01-03

### Added
- Font styling options
- Background color configuration
- Improved styling section in Form Builder

## [1.0.0] - 2025-01-03

### Added
- Initial release
- AI-powered form generation using Groq, Google Gemini, or Meta Llama
- Manual form builder with field picker
- Pre-configured CRM field presets (15+ fields)
- Drag-and-drop field reordering
- Live preview while editing
- Custom styling options:
  - Button color
  - Border radius
  - Label position
  - Button width
  - Custom CSS
- Form management:
  - Create, edit, delete forms
  - Preview forms
  - Copy shortcode
  - Active/Inactive status
- Submission tracking:
  - View all submissions
  - Track CRM sync status
  - View submission details
- Settings page:
  - AI provider configuration
  - API key management
  - Default CRM Form ID
  - Default messages
- REST API endpoints:
  - Form generation
  - Form CRUD operations
  - Form submission
  - Submission retrieval
- WordPress coding standards compliance
- Responsive admin interface
- Accessibility improvements

### Security
- Nonce verification on all forms
- Capability checks on admin pages
- Input sanitization and output escaping
- REST API authentication

---

## Version History

### Versioning Scheme

We use [Semantic Versioning](https://semver.org/):

- **MAJOR** version for incompatible API changes
- **MINOR** version for backwards-compatible functionality additions
- **PATCH** version for backwards-compatible bug fixes

### Release Cycle

- **Stable releases** are published to the WordPress plugin repository
- **Development versions** are available on GitHub
- **Security patches** are released as needed

[Unreleased]: https://github.com/vplugins/ai-crm-form/compare/v1.4.1...HEAD
[1.4.1]: https://github.com/vplugins/ai-crm-form/compare/v1.4.0...v1.4.1
[1.4.0]: https://github.com/vplugins/ai-crm-form/compare/v1.3.0...v1.4.0
[1.3.0]: https://github.com/vplugins/ai-crm-form/compare/v1.2.0...v1.3.0
[1.2.0]: https://github.com/vplugins/ai-crm-form/compare/v1.1.1...v1.2.0
[1.1.1]: https://github.com/vplugins/ai-crm-form/compare/v1.1.0...v1.1.1
[1.1.0]: https://github.com/vplugins/ai-crm-form/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/vplugins/ai-crm-form/releases/tag/v1.0.0

