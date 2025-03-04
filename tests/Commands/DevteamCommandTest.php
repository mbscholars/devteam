<?php

namespace mbscholars\Devteam\Tests\Commands;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

it('can generate frontend task prompt', function () {
    // Mock the HTTP response for AI follow-up questions
    Http::fake([
        'https://api.openai.com/v1/chat/completions' => Http::response([
            'choices' => [
                [
                    'message' => [
                        'content' => '["What specific Vue components will this feature interact with?", "Are there any specific browser constraints?"]',
                    ],
                ],
            ],
        ], 200),
    ]);

    // Execute the command
    $taskName = 'TestFrontendTask'.Str::random(5);

    $this->artisan('devteam', ['name' => $taskName])
        ->expectsChoice('Which department is this task for?', 'frontend', ['frontend', 'backend', 'database'])
        ->expectsChoice('What is the skill level for implementing this task?', 'mid-level', ['junior', 'mid-level', 'senior', 'expert'])
        ->expectsQuestion('What is the business goal of this frontend task?', 'Test business goal')
        ->expectsQuestion('How does this UI/UX fit into the overall user journey?', 'Test user journey')
        ->expectsQuestion('What key pain points should be addressed in the design?', 'Test pain points')
        ->expectsQuestion('What are the specific UI components or pages that need to be created or modified?', 'Test components')
        ->expectsQuestion('Are there existing design systems or component libraries to follow?', 'Vue 3 component library')
        ->expectsQuestion('What are the primary colors, typography, and branding guidelines?', 'Test branding')
        ->expectsQuestion('Are there any design assets (images, icons, illustrations) that need to be used?', 'Test assets')
        ->expectsQuestion('Is there an existing Figma, Sketch, or Adobe XD file for reference?', 'Yes, Figma file')
        ->expectsQuestion('Should animations or micro-interactions be included?', 'Yes')
        ->expectsQuestion('Should the design be accessible (WCAG compliance, ARIA roles, contrast)?', 'Yes, WCAG AA')
        ->expectsQuestion('What are the expected user interactions?', 'Test interactions')
        ->expectsQuestion('Are there edge cases that need to be handled for user interactions?', 'Test edge cases')
        ->expectsQuestion('Are there any real-time features?', 'No')
        ->expectsQuestion('What should happen in case of errors or slow connections?', 'Show error messages')
        ->expectsQuestion('Which devices and screen sizes should this be optimized for?', 'All devices')
        ->expectsQuestion('What are the browser compatibility requirements?', 'Modern browsers')
        ->expectsQuestion('Are there performance requirements?', 'Fast loading')
        ->expectsQuestion('Should this support dark mode or theme switching?', 'Yes')
        ->expectsQuestion('Which backend APIs does the frontend need to interact with?', 'Test API')
        ->expectsQuestion('Are there any authentication/authorization flows to consider?', 'JWT auth')
        ->expectsQuestion('How should form validations be handled?', 'Client-side validation')
        ->expectsQuestion('Should the frontend store/cache any data locally?', 'LocalStorage')
        // AI follow-up questions
        ->expectsQuestion('What specific Vue components will this feature interact with?', 'TestComponent')
        ->expectsQuestion('Are there any specific browser constraints?', 'No IE11 support')
        ->assertExitCode(0);

    // Check if file exists
    $filePath = base_path("features/$taskName/prompt.md");
    expect(File::exists($filePath))->toBeTrue();

    // Check content
    $content = File::get($filePath);
    expect($content)->toContain('Frontend Task: '.$taskName);
    expect($content)->toContain('Implementation Skill Level: mid-level');
    expect($content)->toContain('Business Goal: Test business goal');
    expect($content)->toContain('Vue 3 component library');
    expect($content)->toContain('JWT auth');
    expect($content)->toContain('TestComponent');

    // Clean up
    if (File::exists($filePath)) {
        File::deleteDirectory(dirname($filePath));
    }
});

it('can generate backend task prompt', function () {
    // Mock the HTTP response for AI follow-up questions
    Http::fake([
        'https://api.openai.com/v1/chat/completions' => Http::response([
            'choices' => [
                [
                    'message' => [
                        'content' => '["What Laravel version is being used?", "Are there any specific coding standards to follow?"]',
                    ],
                ],
            ],
        ], 200),
    ]);

    // Execute the command
    $taskName = 'TestBackendTask'.Str::random(5);

    $this->artisan('devteam', ['name' => $taskName])
        ->expectsChoice('Which department is this task for?', 'backend', ['frontend', 'backend', 'database'])
        ->expectsChoice('What is the skill level for implementing this task?', 'senior', ['junior', 'mid-level', 'senior', 'expert'])
        ->expectsQuestion('What is the business goal of this backend feature?', 'Test business goal')
        ->expectsQuestion('Who are the primary users interacting with this functionality?', 'Admin users')
        ->expectsQuestion('Does this impact any existing workflows or require backward compatibility?', 'Yes')
        ->expectsQuestion('Which API endpoints or services need to be created or modified?', 'User API')
        ->expectsQuestion('What should be the expected request and response formats?', 'JSON')
        ->expectsQuestion('What is the expected data flow between services?', 'RESTful')
        ->expectsQuestion('Are there new database tables, columns, or indexes required?', 'New user_logs table')
        ->expectsQuestion('Should data be cached?', 'Yes, Redis')
        ->expectsQuestion('Are there any bulk operations or complex queries that need optimization?', 'Batch processing')
        ->expectsQuestion('How should database migrations and schema changes be handled?', 'Laravel migrations')
        ->expectsQuestion('What authentication mechanisms are required?', 'Sanctum')
        ->expectsQuestion('What authorization rules should be enforced?', 'RBAC')
        ->expectsQuestion('Are there any sensitive data handling or encryption requirements?', 'Encrypt PII')
        ->expectsQuestion('Are there rate-limiting, throttling, or API abuse protections needed?', 'Rate limiting')
        ->expectsQuestion('What is the expected traffic/load this feature should handle?', '500 req/min')
        ->expectsQuestion('Should the API be rate-limited or load-balanced?', 'Load balanced')
        ->expectsQuestion('Are there asynchronous processing requirements?', 'Queue jobs')
        ->expectsQuestion('Should this support horizontal scaling, containerization, or Kubernetes?', 'Docker')
        ->expectsQuestion('How should errors and exceptions be handled?', 'Standard responses')
        ->expectsQuestion('Are there logging and monitoring requirements?', 'ELK stack')
        ->expectsQuestion('Should system metrics be tracked?', 'Response times')
        ->expectsQuestion('Are there any external APIs, SDKs, or third-party services involved?', 'Payment gateway')
        ->expectsQuestion('What are the CI/CD pipeline requirements?', 'GitHub Actions')
        ->expectsQuestion('Should feature flags or blue-green deployments be used?', 'Feature flags')
        // AI follow-up questions
        ->expectsQuestion('What Laravel version is being used?', 'Laravel 12')
        ->expectsQuestion('Are there any specific coding standards to follow?', 'PSR-12')
        ->assertExitCode(0);

    // Check if file exists
    $filePath = base_path("features/$taskName/prompt.md");
    expect(File::exists($filePath))->toBeTrue();

    // Check content
    $content = File::get($filePath);
    expect($content)->toContain('Backend Task: '.$taskName);
    expect($content)->toContain('Skill Level: senior');
    expect($content)->toContain('Business Goal: Test business goal');
    expect($content)->toContain('Laravel 12');
    expect($content)->toContain('PSR-12');

    // Clean up
    if (File::exists($filePath)) {
        File::deleteDirectory(dirname($filePath));
    }
});

it('can generate database task prompt', function () {
    // Mock the HTTP response for AI follow-up questions
    Http::fake([
        'https://api.openai.com/v1/chat/completions' => Http::response([
            'choices' => [
                [
                    'message' => [
                        'content' => '["What database engine is being used?", "Are there any specific naming conventions for database objects?"]',
                    ],
                ],
            ],
        ], 200),
    ]);

    // Execute the command
    $taskName = 'TestDatabaseTask'.Str::random(5);

    $this->artisan('devteam', ['name' => $taskName])
        ->expectsChoice('Which department is this task for?', 'database', ['frontend', 'backend', 'database'])
        ->expectsChoice('What is the skill level for implementing this task?', 'expert', ['junior', 'mid-level', 'senior', 'expert'])
        ->expectsQuestion('What is the business goal of this database design?', 'Improve performance')
        ->expectsQuestion('Who are the primary users or systems interacting with this database?', 'Backend services')
        ->expectsQuestion('Does this impact any existing database schemas or require backward compatibility?', 'Yes')
        ->expectsQuestion('What are the main tables/entities needed in this design?', 'Users, Orders, Products')
        ->expectsQuestion('What are the key relationships between these entities?', 'One-to-many')
        ->expectsQuestion('What constraints (unique, foreign keys, checks) are required?', 'FK constraints')
        ->expectsQuestion('What indexes should be created for performance?', 'Indexes on search fields')
        ->expectsQuestion('What specific data types are required for key fields?', 'UUID for IDs')
        ->expectsQuestion('What data validation rules should be enforced at the database level?', 'NOT NULL constraints')
        ->expectsQuestion('Are there default values or auto-generated fields?', 'Timestamps')
        ->expectsQuestion('When should the migration be scheduled for?', '2023-10-15')
        ->expectsQuestion('Is there existing data that needs to be migrated or transformed?', 'Yes')
        ->expectsQuestion('What is the rollback plan if the migration fails?', 'Transaction-based')
        ->expectsChoice('Should database factories be created?', 'yes', ['yes', 'no'])
        ->expectsChoice('Should database seeders be created?', 'yes', ['yes', 'no'])
        ->expectsQuestion('What is the expected data volume and growth rate?', '1M records')
        ->expectsQuestion('What are the most common query patterns and access patterns?', 'Read-heavy')
        ->expectsQuestion('Is table partitioning or sharding required?', 'Partitioning')
        ->expectsQuestion('What caching strategies should be implemented?', 'Redis')
        ->expectsQuestion('Is there sensitive data that requires special handling?', 'PII encryption')
        ->expectsQuestion('What database-level access controls are needed?', 'Role-based')
        ->expectsQuestion('Are there auditing or logging requirements?', 'Audit trails')
        ->expectsQuestion('Are there specific compliance requirements (GDPR, HIPAA, etc.)?', 'GDPR')
        ->expectsQuestion('What test data requirements are there?', 'Realistic data')
        ->expectsQuestion('What ongoing maintenance procedures are needed?', 'Index maintenance')
        // AI follow-up questions
        ->expectsQuestion('What database engine is being used?', 'MySQL 8.0')
        ->expectsQuestion('Are there any specific naming conventions for database objects?', 'Snake case')
        ->assertExitCode(0);

    // Check if file exists
    $filePath = base_path("features/$taskName/prompt.md");
    expect(File::exists($filePath))->toBeTrue();

    // Check content
    $content = File::get($filePath);
    expect($content)->toContain('Database Design Task: '.$taskName);
    expect($content)->toContain('Skill Level: expert');
    expect($content)->toContain('Business Goal: Improve performance');
    expect($content)->toContain('MySQL 8.0');
    expect($content)->toContain('Snake case');

    // Clean up
    if (File::exists($filePath)) {
        File::deleteDirectory(dirname($filePath));
    }
});

it('handles API errors gracefully when generating follow-up questions', function () {
    // Mock the HTTP response to fail
    Http::fake([
        'https://api.openai.com/v1/chat/completions' => Http::response('', 500),
    ]);

    // Execute the command
    $taskName = 'TestErrorTask'.Str::random(5);

    $this->artisan('devteam', ['name' => $taskName])
        ->expectsChoice('Which department is this task for?', 'frontend', ['frontend', 'backend', 'database'])
        ->expectsChoice('What is the skill level for implementing this task?', 'junior', ['junior', 'mid-level', 'senior', 'expert'])
        ->expectsQuestion('What is the business goal of this frontend task?', 'Test business goal')
        // Continue with minimum required questions...
        ->expectsQuestion('How does this UI/UX fit into the overall user journey?', 'Test user journey')
        ->expectsQuestion('What key pain points should be addressed in the design?', 'Test pain points')
        // ... (other questions)
        // Should fall back to default questions when API fails
        ->expectsQuestion('What specific Vue components will this feature interact with?', 'None')
        ->assertExitCode(0);

    // Check if file exists despite API error
    $filePath = base_path("features/$taskName/prompt.md");
    expect(File::exists($filePath))->toBeTrue();

    // Clean up
    if (File::exists($filePath)) {
        File::deleteDirectory(dirname($filePath));
    }
});
