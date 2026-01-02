# Contributing to AI CRM Form

Thank you for your interest in contributing to AI CRM Form! This document provides guidelines and information for contributors.

## Table of Contents

- [Code of Conduct](#code-of-conduct)
- [Getting Started](#getting-started)
- [Development Setup](#development-setup)
- [Coding Standards](#coding-standards)
- [Pull Request Process](#pull-request-process)
- [Reporting Bugs](#reporting-bugs)
- [Suggesting Features](#suggesting-features)

## Code of Conduct

This project adheres to a code of conduct. By participating, you are expected to uphold this code.

### Our Standards

- Use welcoming and inclusive language
- Be respectful of differing viewpoints and experiences
- Gracefully accept constructive criticism
- Focus on what is best for the community
- Show empathy towards other community members

## Getting Started

1. Fork the repository on GitHub
2. Clone your fork locally
3. Set up the development environment
4. Create a new branch for your changes
5. Make your changes
6. Test your changes
7. Submit a pull request

## Development Setup

### Prerequisites

- PHP 7.4 or higher
- Composer
- Node.js 18 or higher
- npm or yarn
- Local WordPress development environment (Local, MAMP, Docker, etc.)

### Installation

```bash
# Clone your fork
git clone https://github.com/YOUR_USERNAME/ai-crm-form.git
cd ai-crm-form

# Install PHP dependencies
composer install

# Install Node dependencies
npm install
```

### Running Tests

```bash
# Run PHP unit tests
composer test

# Run specific test file
composer test -- --filter TestClassName

# Run with coverage
composer test:coverage
```

### Linting

```bash
# Check coding standards
composer lint

# Fix coding standards automatically
composer lint:fix

# Run PHP CodeSniffer
./vendor/bin/phpcs

# Run PHP Code Beautifier
./vendor/bin/phpcbf

# Run Prettier for JS/CSS
npm run format

# Check JS/CSS formatting
npm run format:check
```

## Coding Standards

### PHP

We follow the [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/).

Key points:
- Use tabs for indentation
- Opening braces on the same line
- Space after control structure keywords
- Yoda conditions for comparisons
- Proper documentation blocks

```php
// Good
if ( 'value' === $variable ) {
    do_something();
}

// Bad
if ($variable == 'value') {
    do_something();
}
```

### JavaScript

We follow the [WordPress JavaScript Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/javascript/).

Key points:
- Use tabs for indentation
- Single quotes for strings
- Semicolons required
- Strict equality comparisons

```javascript
// Good
if ( 'value' === variable ) {
    doSomething();
}

// Bad
if (variable == "value") {
    doSomething()
}
```

### CSS

We follow the [WordPress CSS Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/css/).

Key points:
- Use tabs for indentation
- One selector per line
- Properties in alphabetical order (where practical)
- Use lowercase and hyphens for class names

```css
/* Good */
.aicrmform-button,
.aicrmform-submit {
    background-color: #0073aa;
    border-radius: 4px;
    color: #fff;
}

/* Bad */
.aicrmformButton, .aicrmformSubmit {
    color: #fff;
    background-color: #0073aa;
    border-radius: 4px;
}
```

### File Naming

- PHP class files: `class-{name}.php` (e.g., `class-form-generator.php`)
- PHP function files: `functions-{name}.php`
- JavaScript files: lowercase with hyphens (e.g., `admin.js`)
- CSS files: lowercase with hyphens (e.g., `admin.css`)

### Documentation

All functions and classes must have proper DocBlocks:

```php
/**
 * Generate a form from a natural language prompt.
 *
 * @since 1.0.0
 *
 * @param string $prompt The natural language description of the form.
 * @param array  $options Optional. Additional options for generation.
 * @return array|WP_Error The generated form config or error.
 */
public function generate_form( $prompt, $options = [] ) {
    // ...
}
```

## Pull Request Process

### Before Submitting

1. **Test your changes** - Ensure all tests pass
2. **Follow coding standards** - Run `composer lint` and fix any issues
3. **Update documentation** - Update README, CHANGELOG if needed
4. **Write descriptive commits** - Use conventional commit messages

### Commit Messages

Use conventional commit format:

```
type(scope): description

[optional body]

[optional footer]
```

Types:
- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation changes
- `style`: Code style changes (formatting, etc.)
- `refactor`: Code refactoring
- `test`: Adding or updating tests
- `chore`: Maintenance tasks

Examples:
```
feat(builder): add drag-and-drop field reordering
fix(api): handle empty form config in submission
docs(readme): update installation instructions
```

### Pull Request Template

When submitting a PR, include:

1. **Description** - What does this PR do?
2. **Related Issue** - Link to related issue(s)
3. **Type of Change** - Bug fix, feature, breaking change, etc.
4. **Testing** - How was this tested?
5. **Checklist**:
   - [ ] Tests pass
   - [ ] Coding standards followed
   - [ ] Documentation updated
   - [ ] CHANGELOG updated

### Review Process

1. Submit your PR
2. Automated checks will run (tests, linting)
3. A maintainer will review your code
4. Address any feedback
5. Once approved, your PR will be merged

## Reporting Bugs

### Before Reporting

- Check if the bug has already been reported
- Ensure you're using the latest version
- Try to reproduce in a clean WordPress installation

### Bug Report Template

```markdown
## Description
A clear description of the bug.

## Steps to Reproduce
1. Go to '...'
2. Click on '...'
3. See error

## Expected Behavior
What you expected to happen.

## Actual Behavior
What actually happened.

## Environment
- WordPress version:
- PHP version:
- Plugin version:
- Browser:
- Theme:

## Additional Context
Any other context, screenshots, or error logs.
```

## Suggesting Features

### Feature Request Template

```markdown
## Problem
Describe the problem this feature would solve.

## Proposed Solution
Describe your proposed solution.

## Alternatives Considered
Describe any alternatives you've considered.

## Additional Context
Any other context, mockups, or examples.
```

## Questions?

If you have questions, please:

1. Check the [documentation](https://github.com/vplugins/ai-crm-form/wiki)
2. Search [existing issues](https://github.com/vplugins/ai-crm-form/issues)
3. Open a [discussion](https://github.com/vplugins/ai-crm-form/discussions)

Thank you for contributing! 🎉

