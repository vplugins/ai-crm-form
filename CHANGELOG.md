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

[Unreleased]: https://github.com/vplugins/ai-crm-form/compare/v1.2.0...HEAD
[1.2.0]: https://github.com/vplugins/ai-crm-form/compare/v1.1.1...v1.2.0
[1.1.1]: https://github.com/vplugins/ai-crm-form/compare/v1.1.0...v1.1.1
[1.1.0]: https://github.com/vplugins/ai-crm-form/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/vplugins/ai-crm-form/releases/tag/v1.0.0

