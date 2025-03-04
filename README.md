# DevTeam - AI-Powered Laravel Development Assistant

[![Latest Version on Packagist](https://img.shields.io/packagist/v/mbscholars/devteam.svg?style=flat-square)](https://packagist.org/packages/mbscholars/devteam)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/mbscholars/devteam/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/mbscholars/devteam/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/mbscholars/devteam/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/mbscholars/devteam/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/mbscholars/devteam.svg?style=flat-square)](https://packagist.org/packages/mbscholars/devteam)

DevTeam is a powerful Laravel package that helps you generate structured prompts for AI-assisted development, analyze your application structure, and document your codebase. It's designed to bridge the gap between your Laravel application and AI tools, making it easier to get high-quality, contextually relevant assistance for your development tasks.

## Features

- **Structured Prompt Generation**: Create detailed, department-specific prompts for frontend, backend, and database tasks
- **AI-Powered Follow-up Questions**: Automatically generate relevant follow-up questions based on your initial task description
- **Application Analysis**: Generate comprehensive summaries of your Laravel application
- **Database Schema Documentation**: Export your database schema in a readable format
- **Frontend Analysis**: Analyze Vue components, JS utilities, and frontend assets

## Installation

You can install the package via composer:

```bash
composer require mbscholars/devteam
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="devteam-config"
```

This is the contents of the published config file:

```php
return [
    // API keys for various services
    'api_keys' => [
        'openai' => env('OPENAI_API_KEY'),
    ],
    
    // AI context generation settings
    'ai_context' => [
        'include_routes' => true,
        'include_db_schema' => true,
    ],
    
    // Summary generation settings
    'summary' => [
        'ignored_directories' => [
            'migrations',
            'resources/views',
            'node_modules',
            'vendor',
            'storage',
            'bootstrap/cache',
            'public',
            'tests',
        ],
        'max_file_size' => 500, // in KB
        'max_scan_depth' => 5,
        'backend_output_path' => 'app/context/summary.json',
        'backend_scan_directories' => [],
    ],
];
```

## Usage

### Generating Task Prompts

The `devteam` command helps you create structured prompts for different types of development tasks:

```bash
php artisan devteam "New User Registration Feature"
```

This interactive command will:

1. Ask you to select a department (frontend, backend, or database)
2. Guide you through department-specific questions to gather requirements
3. Use AI to generate follow-up questions for missing information
4. Save the structured prompt to a file in the `features/{TaskName}/prompt.md` directory

#### Example Frontend Prompt

```markdown
# Frontend Task: New User Registration Feature

## Implementation Style
- **Implementation Skill Level:** mid-level

## 1. General Context & Business Objective
- **Business Goal:** Increase user sign-ups by 20%
- **User Journey Context:** First touchpoint for new users
- **Pain Points to Address:** Current form is too complex and has high abandonment rate

## 2. UI/UX & Design
- **Components/Pages:** Registration form with multi-step process
- **Design System:** Follow our Vue 3 component library
- **Branding Guidelines:** Use primary brand colors (#3498db, #2ecc71)
...
```

### Dumping Database Schema

Export your database schema to a text file for documentation or AI context:

```bash
php artisan schema:dump database-schema.txt
```

This will create a readable text file with tables, columns, data types, and relationships.

### Generating Application Summary

Create a comprehensive JSON summary of your Laravel application:

```bash
php artisan app:summary --output=app-summary.json
```

This command analyzes:
- Controllers, models, and their relationships
- Service providers and middleware
- Commands and jobs
- Routes and endpoints
- Database schema
- Composer packages

### Generating Frontend Summary

Create a JSON summary of your frontend codebase:

```bash
php artisan app:frontend-summary --output=frontend-summary.json
```

This command analyzes:
- Vue components and their structure
- Composition API usage
- JS utilities and composables
- CSS/SCSS assets
- NPM packages
- Vite configuration

## Use Cases

### AI-Assisted Development

1. Generate a structured prompt for your task:
   ```bash
   php artisan devteam "User Profile Page"
   ```

2. Export your application context:
   ```bash
   php artisan app:summary
   php artisan app:frontend-summary
   php artisan schema:dump database-schema.txt
   ```

3. Use the generated prompt and context files with AI tools like Claude or GPT to get highly relevant, contextual assistance.

### Documentation

Use the summary and schema commands to automatically generate documentation about your application structure:

```bash
php artisan app:summary --output=docs/application-structure.json
php artisan schema:dump docs/database-schema.txt
```

### Onboarding New Developers

The summary commands provide a quick overview of your application structure, making it easier for new developers to understand the codebase:

```bash
php artisan app:summary --output=onboarding/backend-overview.json
php artisan app:frontend-summary --output=onboarding/frontend-overview.json
```

## Advanced Usage

### Customizing Ignored Directories

You can customize which directories are ignored during scanning by modifying the `ignored_directories` array in the config file.

### Setting Maximum File Size and Scan Depth

To prevent performance issues with large codebases, you can set limits on file size and directory depth:

```php
'max_file_size' => 500, // in KB
'max_scan_depth' => 5,
```

### Adding Custom Scan Directories

You can add custom directories to scan by adding them to the `backend_scan_directories` array:

```php
'backend_scan_directories' => [
    'app/Services',
    'app/Repositories',
],
```

## Working with Vue 3 Composition API

DevTeam is optimized for Vue 3 Composition API projects. When generating frontend summaries, it will detect:

- `<script setup>` usage
- Imported composables and hooks
- Component props and emits
- Composition API functions like `ref`, `computed`, and `watch`

This information is included in the frontend summary to provide better context for AI assistance.

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Sunday Mba](https://github.com/mbscholars)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
