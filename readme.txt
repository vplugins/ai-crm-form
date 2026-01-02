=== AI CRM Form ===
Contributors: rajanvijayan
Donate link: https://example.com/donate
Tags: forms, crm, lead generation, ai, contact form
Requires at least: 5.8
Tested up to: 6.4
Requires PHP: 8.0
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Create beautiful lead capture forms powered by AI with seamless CRM integration.

== Description ==

AI CRM Form is a powerful WordPress plugin that allows you to create professional lead capture forms using AI or a manual drag-and-drop builder. All submissions are automatically synced to your CRM.

= Features =

* **AI-Powered Form Generation** - Simply describe the form you want and AI will create it for you
* **Drag & Drop Builder** - Manually build forms with an intuitive interface
* **CRM Integration** - Automatic sync of form submissions to your CRM
* **Pre-configured CRM Fields** - Quick setup with field presets that map directly to CRM
* **Live Preview** - See changes in real-time as you build
* **Custom Styling** - Customize colors, spacing, and add custom CSS
* **Submission Tracking** - View and manage all form submissions
* **Responsive Design** - Forms look great on all devices

= Supported AI Providers =

* Groq (Recommended)
* Google Gemini
* Meta Llama

= Use Cases =

* Contact forms
* Lead capture forms
* Newsletter signup forms
* Event registration forms
* Quote request forms
* Survey forms

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/ai-crm-form` directory, or install through the WordPress plugins screen
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Go to AI CRM Forms > Settings to configure your API keys
4. Start creating forms!

== Frequently Asked Questions ==

= What AI providers are supported? =

Currently, we support Groq, Google Gemini, and Meta Llama. Groq is recommended for best results.

= Do I need an API key? =

Yes, you need an API key from one of the supported AI providers to use the AI form generation feature. However, you can still create forms manually without an API key.

= How do I embed a form? =

Use the shortcode `[ai_crm_form id="X"]` where X is your form ID. You can find the shortcode on the Forms page after creating a form.

= Can I customize the form styling? =

Yes! Each form has styling options including colors, border radius, button width, and label position. You can also add custom CSS.

= Is it compatible with my theme? =

Yes, AI CRM Form is designed to work with any properly coded WordPress theme. The forms use minimal styling that adapts to your theme.

= Where are submissions stored? =

Submissions are stored in your WordPress database and can be viewed in AI CRM Forms > Submissions. They are also synced to your configured CRM.

== Screenshots ==

1. Form Builder - Create forms with AI or manually
2. Add Field - Choose from CRM fields or basic field types
3. Forms List - Manage all your forms
4. Submissions - View and track form submissions
5. Settings - Configure AI and CRM integration

== Changelog ==

= 1.0.0 =
* Initial release
* AI-powered form generation
* Manual form builder with drag-and-drop
* CRM field presets
* Live preview
* Custom styling options
* Submission tracking
* Responsive design

== Upgrade Notice ==

= 1.0.0 =
Initial release of AI CRM Form.

== Privacy Policy ==

AI CRM Form stores form submissions in your WordPress database. When configured, submissions are also sent to your CRM. The plugin communicates with your chosen AI provider only when generating forms.

Data collected:
* Form submission data (as configured in your forms)
* IP addresses of form submitters
* Timestamps of submissions

No data is sent to third parties except:
* Your configured CRM (for form submissions)
* Your chosen AI provider (for form generation only)

