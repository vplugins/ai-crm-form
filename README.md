# AI CRM Form

[![WordPress](https://img.shields.io/badge/WordPress-5.8%2B-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPL%20v2-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![Tests](https://github.com/vplugins/ai-crm-form/actions/workflows/tests.yml/badge.svg)](https://github.com/vplugins/ai-crm-form/actions)

A powerful WordPress plugin for creating AI-powered lead capture forms with CRM integration.

## Features

- 🤖 **AI-Powered Form Generation** - Describe your form in natural language and let AI build it
- 🎨 **Drag & Drop Builder** - Manually create forms with an intuitive interface
- ☁️ **CRM Integration** - Seamless integration with your CRM system
- 📱 **Responsive Design** - Forms look great on all devices
- ⚡ **Live Preview** - See changes in real-time as you build
- 🎯 **Field Presets** - Pre-configured CRM fields for quick setup
- 🎨 **Custom Styling** - Customize colors, borders, and add custom CSS
- 📊 **Submission Tracking** - View and manage all form submissions

## Requirements

- WordPress 5.8 or higher
- PHP 7.4 or higher
- MySQL 5.7 or higher

## Installation

### From WordPress Admin

1. Go to **Plugins > Add New**
2. Search for "AI CRM Form"
3. Click **Install Now** and then **Activate**

### Manual Installation

1. Download the plugin zip file
2. Go to **Plugins > Add New > Upload Plugin**
3. Upload the zip file and click **Install Now**
4. Activate the plugin

### From Source

```bash
cd wp-content/plugins/
git clone https://github.com/vplugins/ai-crm-form.git
cd ai-crm-form
composer install
```

## Configuration

1. Go to **AI CRM Forms > Settings**
2. Add your AI Provider API key (Groq, Google Gemini, or Meta Llama)
3. Configure your default CRM Form ID
4. Enable the plugin

## Usage

### Creating a Form with AI

1. Go to **AI CRM Forms > Form Builder**
2. Click **Generate with AI**
3. Describe your form (e.g., "Create a contact form with name, email, and message")
4. Review and customize the generated form
5. Click **Save Form**

### Creating a Form Manually

1. Go to **AI CRM Forms > Form Builder**
2. Click **Add Field** and select from available field types
3. Configure each field's properties
4. Add styling options as needed
5. Click **Save Form**

### Embedding Forms

Use the shortcode to embed your form:

```
[ai_crm_form id="1"]
```

## Development

### Prerequisites

- Node.js 18+
- PHP 7.4+
- Composer

### Setup

```bash
# Install PHP dependencies
composer install

# Install Node dependencies
npm install

# Run tests
composer test

# Run linting
composer lint

# Fix coding standards
composer lint:fix
```

### File Structure

```
ai-crm-form/
├── assets/
│   ├── css/
│   │   ├── admin.css
│   │   └── form.css
│   └── js/
│       ├── admin.js
│       └── form.js
├── includes/
│   ├── class-admin-settings.php
│   ├── class-crm-api.php
│   ├── class-field-mapping.php
│   ├── class-form-generator.php
│   ├── class-form-shortcode.php
│   └── class-rest-api.php
├── templates/
├── tests/
│   ├── bootstrap.php
│   ├── test-form-generator.php
│   └── test-rest-api.php
├── vendor/
├── ai-crm-form.php
├── composer.json
├── package.json
├── phpcs.xml
├── phpunit.xml
└── README.md
```

## Contributing

Please read [CONTRIBUTING.md](CONTRIBUTING.md) for details on our code of conduct and the process for submitting pull requests.

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for a list of changes.

## License

This project is licensed under the GPL v2 License - see the [LICENSE](LICENSE) file for details.

## Support

- [Documentation](https://github.com/vplugins/ai-crm-form/wiki)
- [Issue Tracker](https://github.com/vplugins/ai-crm-form/issues)
- [Discussions](https://github.com/vplugins/ai-crm-form/discussions)

## Credits

- Built with [AI Engine](https://github.com/rajanvijayan/ai-engine) for AI integration
- Icons by [Dashicons](https://developer.wordpress.org/resource/dashicons/)

