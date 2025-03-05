<?php

namespace mbscholars\Devteam\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class DevteamCommand extends Command
{
    protected $signature = 'devteam:feature {name?}';

    protected $description = 'Generate a structured prompt and store it in a file';

    protected $apiKey;

    protected $apiEndpoint = 'https://api.openai.com/v1/chat/completions';

    public function __construct()
    {
        parent::__construct();
        $this->apiKey = config('devteam.api_keys.openai');
    }

    public function handle(): int
    {
        // Run all devteam imports
        $this->info('Generating updated backend summary');
        $this->call('devteam:backend-summary');

        $this->info('Generating updated frontend summary');
        $this->call('devteam:frontend-summary');

        $this->info('Generating updated database schema');
        $this->call('devteam:db');

        $this->line('');
        $this->info('🚀 <fg=yellow;options=bold>DEVTEAM PROMPT GENERATOR</> 🚀');
        $this->line('');

        $department = $this->choice(
            '📋 <fg=green>Which department is this task for?</>',
            ['frontend', 'backend', 'database'],
            0
        );

        $name = $this->argument('name') ?? $this->ask('📝 <fg=green>What is the task title?</>');

        $skillLevel = $this->choice(
            '👨‍💻 <fg=green>What is the skill level for implementing this task?</>',
            ['junior', 'mid-level', 'senior', 'expert'],
            1
        );

        // Different questions based on department
        if ($department === 'frontend') {
            $this->line('');
            $this->info('📱 <fg=yellow;options=bold>FRONTEND TASK DETAILS</>');
            $this->line('');

            // Section 1: General Context
            $this->line('<fg=blue;options=bold>1. GENERAL CONTEXT & BUSINESS OBJECTIVE</>');
            $businessGoal = $this->ask('   What is the business goal of this frontend task?',
                'Improve user engagement and conversion rate');
            $userJourney = $this->ask('   How does this UI/UX fit into the overall user journey?',
                'Part of the main conversion funnel');
            $painPoints = $this->ask('   What key pain points should be addressed in the design?',
                'Simplify complex interactions and improve clarity');

            // New question about frontend blueprint
            $frontendBlueprint = $this->ask('Is frontend blueprint available? (path to file)',
                'devteam/contexts/frontend-summary.json');

            // Section 2: UI/UX & Design
            $this->line('');
            $this->line('<fg=blue;options=bold>2. UI/UX & DESIGN</>');
            $components = $this->ask('   What are the specific UI components or pages that need to be created or modified?',
                'Main dashboard page and navigation components');

            // New question about components folder
            $componentsFolder = $this->ask('Where should the components be stored?',
                'resources/js/components');

            $designSystem = $this->ask('   Are there existing design systems or component libraries to follow?',
                'Yes, follow our internal Vue component library');
            $branding = $this->ask('   What are the primary colors, typography, and branding guidelines?',
                'Use the company style guide with primary brand colors');
            $assets = $this->ask('   Are there any design assets (images, icons, illustrations) that need to be used?',
                'Use icons from our design system');
            $designFiles = $this->ask('   Is there an existing Figma, Sketch, or Adobe XD file for reference?',
                'Yes, Figma design file is available');
            $animations = $this->ask('   Should animations or micro-interactions be included?',
                'Yes, subtle animations for state changes');
            $accessibility = $this->ask('   Should the design be accessible (WCAG compliance, ARIA roles, contrast)?',
                'Yes, WCAG AA compliance required');

            // Section 3: User Interactions
            $this->line('');
            $this->line('<fg=blue;options=bold>3. USER INTERACTIONS & BEHAVIOR</>');
            $interactions = $this->ask('   What are the expected user interactions?',
                'Form submissions, filtering, and sorting data');
            $edgeCases = $this->ask('   Are there edge cases that need to be handled for user interactions?',
                'Handle form validation errors and empty states');
            $realtime = $this->ask('   Are there any real-time features?',
                'No real-time features required');
            $errors = $this->ask('   What should happen in case of errors or slow connections?',
                'Show friendly error messages and loading states');

            // Section 4: Performance & Compatibility
            $this->line('');
            $this->line('<fg=blue;options=bold>4. PERFORMANCE, COMPATIBILITY & RESPONSIVENESS</>');
            $devices = $this->ask('   Which devices and screen sizes should this be optimized for?',
                'Desktop, tablet, and mobile devices');
            $browsers = $this->ask('   What are the browser compatibility requirements?',
                'Chrome, Safari, Firefox, Edge (latest versions)');
            $performance = $this->ask('   Are there performance requirements?',
                'Optimize for fast initial load and minimal re-renders');
            $darkMode = $this->ask('   Should this support dark mode or theme switching?',
                'Yes, support both light and dark themes');

            // Section 5: Integration
            $this->line('');
            $this->line('<fg=blue;options=bold>5. INTEGRATION & DATA HANDLING</>');
            $apis = $this->ask('   Which backend APIs does the frontend need to interact with?',
                'User API and Content API');
            $auth = $this->ask('   Are there any authentication/authorization flows to consider?',
                'JWT authentication required');
            $validation = $this->ask('   How should form validations be handled?',
                'Client-side validation with server-side confirmation');
            $storage = $this->ask('   Should the frontend store/cache any data locally?',
                'Cache user preferences in LocalStorage');

            // New question about testing
            $this->line('');
            $this->line('<fg=blue;options=bold>6. TESTING REQUIREMENTS</>');
            $shouldTest = $this->choice('   Should tests be written for this feature?', ['yes', 'no'], 0);
            $testTypes = '';
            if ($shouldTest === 'yes') {
                $testTypes = $this->ask('What types of tests should be included?',
                    'Unit tests for components and integration tests for user flows');
            }

            // New question about stopping for confirmation
            $stopAndConfirm = $this->choice('   Should implementation stop and confirm after each file?', ['yes', 'no'], 1);

            $promptContent = <<<PROMPT
# Frontend Task: $name

## Implementation Style
- **Implementation Skill Level:** $skillLevel
- **Frontend Blueprint:** $frontendBlueprint
- **Components Location:** $componentsFolder
- **Stop and Confirm After Each File:** $stopAndConfirm
- ** Judiciously Use front end components as necessary as found in frontend blueprint

## 1. General Context & Business Objective
- **Business Goal:** $businessGoal
- **User Journey Context:** $userJourney
- **Pain Points to Address:** $painPoints

## 2. UI/UX & Design
- **Components/Pages:** $components
- **Design System:** $designSystem
- **Branding Guidelines:** $branding
- **Design Assets:** $assets
- **Design Files:** $designFiles
- **Animations:** $animations
- **Accessibility:** $accessibility

## 3. User Interactions & Behavior
- **Expected Interactions:** $interactions
- **Edge Cases:** $edgeCases
- **Real-time Features:** $realtime
- **Error Handling:** $errors

## 4. Performance, Compatibility & Responsiveness
- **Device Optimization:** $devices
- **Browser Compatibility:** $browsers
- **Performance Requirements:** $performance
- **Theme Support:** $darkMode

## 5. Integration & Data Handling
- **API Integration:** $apis
- **Authentication:** $auth
- **Validation Approach:** $validation
- **Local Storage:** $storage

## 6. Testing Requirements
- **Should Tests Be Written:** $shouldTest

PROMPT;

            if ($shouldTest === 'yes') {
                $promptContent .= "\n- **Test Types:** $testTypes";
            }

        } elseif ($department === 'backend') {
            $this->line('');
            $this->info('⚙️ <fg=yellow;options=bold>BACKEND TASK DETAILS</>');
            $this->line('');

            // Section 1: Business Objective & Context
            $this->line('<fg=blue;options=bold>1. BUSINESS OBJECTIVE & CONTEXT</>');
            $businessGoal = $this->ask('   What is the business goal of this backend feature?',
                'Improve data processing efficiency and system reliability');
            $primaryUsers = $this->ask('   Who are the primary users interacting with this functionality?',
                'Internal admin users and customer-facing applications');
            $compatibility = $this->ask('   Does this impact any existing workflows or require backward compatibility?',
                'Yes, must maintain compatibility with existing API consumers');

            // New questions about application architecture and blueprints
            $appBlueprint = $this->ask('   Is application blueprint available? (path to file)',
                'devteam/contexts/app-summary.json');
            $architecture = $this->ask('   What application architecture should be followed?',
                'As per application blueprint');

            // Section 2: API & Services
            $this->line('');
            $this->line('<fg=blue;options=bold>2. API & SERVICES</>');
            $endpoints = $this->ask('   Which API endpoints or services need to be created or modified?',
                'User management endpoints and authentication service');
            $formats = $this->ask('   What should be the expected request and response formats?',
                'JSON with standard API response envelope');
            $dataFlow = $this->ask('   What is the expected data flow between services?',
                'RESTful API calls between microservices with event broadcasting');

            // Section 3: Database & Storage
            $this->line('');
            $this->line('<fg=blue;options=bold>3. DATABASE & STORAGE</>');
            $dbChanges = $this->ask('   Are there new database tables, columns, or indexes required?',
                'New user_preferences table and indexes on frequently queried columns');
            $caching = $this->ask('   Should data be cached?',
                'Yes, cache frequently accessed data in Redis');
            $optimization = $this->ask('   Are there any bulk operations or complex queries that need optimization?',
                'Optimize reporting queries and implement batch processing');
            $migrations = $this->ask('   How should database migrations and schema changes be handled?',
                'Use Laravel migrations with zero-downtime deployment strategy');

            // Section 4: Authentication, Authorization & Security
            $this->line('');
            $this->line('<fg=blue;options=bold>4. AUTHENTICATION, AUTHORIZATION & SECURITY</>');
            $authMech = $this->ask('   What authentication mechanisms are required?',
                'JWT tokens with refresh mechanism');
            $authRules = $this->ask('   What authorization rules should be enforced?',
                'Role-based access control with granular permissions');
            $sensitiveData = $this->ask('   Are there any sensitive data handling or encryption requirements?',
                'Encrypt PII and implement proper data masking');
            $rateLimit = $this->ask('   Are there rate-limiting, throttling, or API abuse protections needed?',
                'Implement rate limiting on public endpoints');

            // Section 5: Performance & Scalability
            $this->line('');
            $this->line('<fg=blue;options=bold>5. PERFORMANCE & SCALABILITY</>');
            $traffic = $this->ask('   What is the expected traffic/load this feature should handle?',
                'Up to 1000 requests per minute during peak hours');
            $loadBalancing = $this->ask('   Should the API be rate-limited or load-balanced?',
                'Implement load balancing across multiple instances');
            $async = $this->ask('   Are there asynchronous processing requirements?',
                'Use Laravel queues for email sending and report generation');
            $scaling = $this->ask('   Should this support horizontal scaling, containerization, or Kubernetes?',
                'Design for horizontal scaling with Docker containers');

            // Section 6: Error Handling & Logging
            $this->line('');
            $this->line('<fg=blue;options=bold>6. ERROR HANDLING & LOGGING</>');
            $errorHandling = $this->ask('   How should errors and exceptions be handled?',
                'Standardized error responses with appropriate HTTP status codes');
            $logging = $this->ask('   Are there logging and monitoring requirements?',
                'Log to ELK stack with structured logging format');
            $metrics = $this->ask('   Should system metrics be tracked?',
                'Track response times, error rates, and database query performance');

            // Section 7: Dependencies & Deployment
            $this->line('');
            $this->line('<fg=blue;options=bold>7. DEPENDENCIES & DEPLOYMENT</>');
            $external = $this->ask('   Are there any external APIs, SDKs, or third-party services involved?',
                'Integration with payment gateway and email service provider');
            $cicd = $this->ask('   What are the CI/CD pipeline requirements?',
                'GitHub Actions with automated testing and staged deployments');
            $featureFlags = $this->ask('   Should feature flags or blue-green deployments be used?',
                'Implement feature flags for gradual rollout');

            // New question about testing
            $this->line('');
            $this->line('<fg=blue;options=bold>8. TESTING REQUIREMENTS</>');
            $shouldTest = $this->choice('   Should tests be written for this feature?', ['yes', 'no'], 0);
            $testTypes = '';
            if ($shouldTest === 'yes') {
                $testTypes = $this->ask('   What types of tests should be included?',
                    'Unit tests, feature tests, and integration tests');
            }

            // New question about stopping for confirmation
            $stopAndConfirm = $this->choice('   Should implementation stop and confirm after each file?', ['yes', 'no'], 1);

            $promptContent = <<<PROMPT
# Backend Task: $name

## Developer Skill Level
- **Skill Level:** $skillLevel
- **Application Blueprint:** $appBlueprint
- **Application Architecture:** $architecture
- **Stop and Confirm from developer After Each File:** $stopAndConfirm


## 1. Business Objective & Context
- **Business Goal:** $businessGoal
- **Primary Users:** $primaryUsers
- **Compatibility Requirements:** $compatibility

## 2. API & Services
- **Endpoints/Services:** $endpoints
- **Request/Response Formats:** $formats
- **Service Data Flow:** $dataFlow

## 3. Database & Storage
- **Database Changes:** $dbChanges
- **Caching Strategy:** $caching
- **Query Optimization:** $optimization
- **Migration Handling:** $migrations

## 4. Authentication, Authorization & Security
- **Authentication Mechanism:** $authMech
- **Authorization Rules:** $authRules
- **Sensitive Data Handling:** $sensitiveData
- **Rate Limiting & Protection:** $rateLimit

## 5. Performance & Scalability
- **Expected Traffic:** $traffic
- **Load Balancing:** $loadBalancing
- **Asynchronous Processing:** $async
- **Scaling Strategy:** $scaling

## 6. Error Handling & Logging
- **Error Handling Approach:** $errorHandling
- **Logging & Monitoring:** $logging
- **System Metrics:** $metrics

## 7. Dependencies & Deployment
- **External Dependencies:** $external
- **CI/CD Requirements:** $cicd
- **Feature Flags/Deployment:** $featureFlags

## 8. Testing Requirements
- **Should Tests Be Written:** $shouldTest

PROMPT;
        } else { // database
            $this->line('');
            $this->info('🗄️ <fg=yellow;options=bold>DATABASE DESIGN TASK DETAILS</>');
            $this->line('');

            // Section 1: Business Objective & Context
            $this->line('<fg=blue;options=bold>1. BUSINESS OBJECTIVE & CONTEXT</>');
            $businessGoal = $this->ask('   What is the business goal of this database design?',
                'Improve data organization and query performance');
            $primaryUsers = $this->ask('   Who are the primary users or systems interacting with this database?',
                'Backend services and reporting systems');
            $compatibility = $this->ask('   Does this impact any existing database schemas or require backward compatibility?',
                'Yes, must maintain compatibility with existing data structures');

            // New question about database blueprint
            $dbBlueprint = $this->ask('   Is database blueprint available? (path to file)',
                'devteam/contexts/db.json');

            // Section 2: Schema Design
            $this->line('');
            $this->line('<fg=blue;options=bold>2. SCHEMA DESIGN</>');
            $tables = $this->ask('   What are the main tables/entities needed in this design?',
                'Let AI decide based on context');
            $relationships = $this->ask('   What are the key relationships between these entities?',
                'Let AI decide based on context');
            $constraints = $this->ask('   What constraints (unique, foreign keys, checks) are required?',
                'Let AI decide based on context');
            $indexes = $this->ask('   What indexes should be created for performance?',
                'Let AI decide based on context');

            // Section 3: Data Types & Validation
            $this->line('');
            $this->line('<fg=blue;options=bold>3. DATA TYPES & VALIDATION</>');
            $dataTypes = $this->ask('   What specific data types are required for key fields?',
                'Let AI decide based on context');
            $validation = $this->ask('   What data validation rules should be enforced at the database level?',
                'Let AI decide based on context');
            $defaultValues = $this->ask('   Are there default values or auto-generated fields?',
                'Let AI decide based on context');

            // Section 4: Migration & Deployment
            $this->line('');
            $this->line('<fg=blue;options=bold>4. MIGRATION & DEPLOYMENT</>');
            $migrationDate = $this->ask('   When should the migration be scheduled for? (YYYY-MM-DD or leave empty for today)',
                date('Y-m-d'));
            $migrationDate = ! empty($migrationDate) ? $migrationDate : date('Y-m-d');
            $dataTransition = $this->ask('   Is there existing data that needs to be migrated or transformed?',
                'Yes, data from legacy tables needs to be migrated with transformation rules');
            $rollbackPlan = $this->ask('   What is the rollback plan if the migration fails?',
                'Transaction-based migration with ability to revert to previous schema');

            // Section 5: Performance & Scaling
            $this->line('');
            $this->line('<fg=blue;options=bold>5. PERFORMANCE & SCALING</>');
            $dataVolume = $this->ask('   What is the expected data volume and growth rate?',
                'Initial 1M records with 10% monthly growth');
            $queryPatterns = $this->ask('   What are the most common query patterns and access patterns?',
                'Let AI decide based on context');
            $partitioning = $this->ask('   Is table partitioning or sharding required?',
                'Consider partitioning large tables by date range');
            $caching = $this->ask('   What caching strategies should be implemented?',
                'Cache frequently accessed lookup data and query results');

            // Section 6: Security & Compliance
            $this->line('');
            $this->line('<fg=blue;options=bold>6. SECURITY & COMPLIANCE</>');
            $sensitiveData = $this->ask('   Is there sensitive data that requires special handling?',
                'PII should be encrypted at rest, payment data should be tokenized');
            $accessControl = $this->ask('   What database-level access controls are needed?',
                'Role-based access with row-level security for multi-tenant data');
            $auditRequirements = $this->ask('   Are there auditing or logging requirements?',
                'Track all data modifications with user ID and timestamp');
            $compliance = $this->ask('   Are there specific compliance requirements (GDPR, HIPAA, etc.)?',
                'GDPR compliance with right to be forgotten capabilities');

            // Section 7: Testing & Maintenance
            $this->line('');
            $this->line('<fg=blue;options=bold>7. TESTING & MAINTENANCE</>');
            $createFactories = $this->choice('   Should database factories be created?', ['yes', 'no'], 0);
            $createSeeders = $this->choice('   Should database seeders be created?', ['yes', 'no'], 0);
            $testData = $this->ask('   What test data requirements are there?',
                'Realistic test data for development and staging environments');
            $maintenance = $this->ask('   What ongoing maintenance procedures are needed?',
                'Regular index maintenance, statistics updates, and integrity checks');

            $promptContent = <<<PROMPT
# Database Design Task: $name

## Database Administrator Skill Level
- **Skill Level:** $skillLevel
- **Database Blueprint:** $dbBlueprint

## 1. Business Objective & Context
- **Business Goal:** $businessGoal
- **Primary Users/Systems:** $primaryUsers
- **Compatibility Requirements:** $compatibility

## 2. Schema Design
- **Main Tables/Entities:** $tables
- **Key Relationships:** $relationships
- **Constraints Required:** $constraints
- **Indexes Required:** $indexes

## 3. Data Types & Validation
- **Specific Data Types:** $dataTypes
- **Validation Rules:** $validation
- **Default Values:** $defaultValues

## 4. Migration & Deployment
- **Migration Date:** $migrationDate
- **Data Migration Strategy:** $dataTransition
- **Rollback Plan:** $rollbackPlan
- **Create Factories:** $createFactories
- **Create Seeders:** $createSeeders

## 5. Performance & Scaling
- **Expected Data Volume:** $dataVolume
- **Common Query Patterns:** $queryPatterns
- **Partitioning Strategy:** $partitioning
- **Caching Strategy:** $caching

## 6. Security & Compliance
- **Sensitive Data Handling:** $sensitiveData
- **Access Controls:** $accessControl
- **Audit Requirements:** $auditRequirements
- **Compliance Requirements:** $compliance

## 7. Testing & Maintenance
- **Test Data Requirements:** $testData
- **Maintenance Procedures:** $maintenance

PROMPT;
        }

        // Generate AI follow-up questions based on the initial prompt
        $this->line('');
        $this->info('🤖 <fg=yellow;options=bold>AI FOLLOW-UP QUESTIONS</>');
        $this->line('');
        $this->line('<fg=blue>Based on your answers, the AI is generating follow-up questions to ensure a complete understanding of the task...</>');

        // Get AI-generated follow-up questions
        $followUpQuestions = $this->getDynamicQuestions($promptContent, $department);

        // Ask follow-up questions and append answers to the prompt
        if (! empty($followUpQuestions)) {
            $this->line('');
            $this->info('📋 <fg=yellow;options=bold>ADDITIONAL DETAILS NEEDED</>');
            $this->line('');

            $additionalDetails = [];
            foreach ($followUpQuestions as $index => $question) {
                $answer = $this->ask('<fg=green>   '.($index + 1).". $question</>");
                $additionalDetails[] = [
                    'question' => $question,
                    'answer' => $answer,
                ];
            }

            // Append additional details to the prompt
            if (! empty($additionalDetails)) {
                $promptContent .= "\n\n## Additional Details\n";
                foreach ($additionalDetails as $detail) {
                    $promptContent .= '- **'.$detail['question'].':** '.$detail['answer']."\n";
                }
            }
        } else {
            $this->line('<fg=yellow>No additional questions needed. The initial information is comprehensive.</>');
        }

        // Create directory structure based on studly case name
        $studlyName = strtolower(str_replace(' ', '-', $name));
        $directoryPath = base_path("devteam/features/$studlyName");
        $filePath = "$directoryPath/prompt.md";

        File::ensureDirectoryExists($directoryPath);
        File::put($filePath, $promptContent);

        $this->line('');
        $this->info('✅ <fg=green;options=bold>Prompt saved successfully!</>');
        $this->line("<fg=yellow>File location:</> <fg=white>$filePath</>");
        $this->line('');

        return self::SUCCESS;
    }

    protected function getDynamicQuestions(string $initialPrompt, string $department): array
    {
        try {
            $systemPrompt = "You are an expert software development assistant specializing in $department development. Based on the task description provided, identify 5-7 critical pieces of information that are missing but would be essential for an ai model to implement this task perfectly. Note the AI model's skill level mentioned in the prompt and adjust your questions accordingly - ask more fundamental questions for junior developers and more advanced/architectural questions for senior/expert developers. Focus on technical details, file locations, integration points, or specific requirements that aren't clear from the initial description. Return ONLY an array of specific questions in JSON format like: [\"Question 1?\", \"Question 2?\"]";

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->apiEndpoint, [
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $initialPrompt],
                ],
                'temperature' => 0.7,
                'max_tokens' => 500,
            ]);

            if ($response->successful()) {
                $content = $response->json('choices.0.message.content');

                // Parse the JSON response
                $jsonStart = strpos($content, '[');
                $jsonEnd = strrpos($content, ']');

                if ($jsonStart !== false && $jsonEnd !== false) {
                    $jsonContent = substr($content, $jsonStart, $jsonEnd - $jsonStart + 1);
                    $questions = json_decode($jsonContent, true);

                    if (json_last_error() === JSON_ERROR_NONE && is_array($questions)) {
                        return $questions;
                    }
                }

                // Fallback: try to extract questions if JSON parsing fails
                preg_match_all('/\d+\.\s+"([^"]+)"/', $content, $matches);
                if (! empty($matches[1])) {
                    return $matches[1];
                }

                // Second fallback: try to extract questions with different format
                preg_match_all('/"([^"]+\?)"/', $content, $matches);
                if (! empty($matches[1])) {
                    return $matches[1];
                }
            }

            $this->warn('Could not generate follow-up questions. Using default questions instead.');

            // Default questions based on department if API call fails
            if ($department === 'frontend') {
                return [
                    'What specific Vue components will this feature interact with?',
                    'Are there any specific browser or device constraints beyond what was mentioned?',
                    'What file structure should be followed for this implementation?',
                    'Are there any specific state management requirements (Pinia, Vuex)?',
                    'Are there any specific testing requirements for this feature?',
                ];
            } elseif ($department === 'backend') {
                return [
                    'What specific Laravel services or providers will this feature interact with?',
                    'Are there any specific database transaction or locking requirements?',
                    'What file structure should be followed for this implementation?',
                    'Are there any specific testing requirements for this feature?',
                    'Are there any specific performance benchmarks this feature needs to meet?',
                ];
            } else { // database
                return [
                    'What specific database engine and version will be used?',
                    'Are there any specific naming conventions to follow for tables, columns, and constraints?',
                    'What is the expected query load (reads vs writes)?',
                    'Are there any specific backup or disaster recovery requirements?',
                    'Should any database views, stored procedures, or functions be created?',
                    'Are there any specific performance metrics this database design needs to meet?',
                    'How should database migrations be versioned and deployed?',
                ];
            }
        } catch (\Exception $e) {
            $this->error('Error generating follow-up questions: '.$e->getMessage());

            return [];
        }
    }
}
