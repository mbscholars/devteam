<?php
 
 
it('can execute a command with arguments', function () {
    // Test with arguments
    $this->artisan('devteam')
            ->expectsChoice('Department?', 'frontend', ['frontend', 'backend'])
            ->expectsQuestion('Task title?', 'Test Task')
            ->expectsQuestion('What is the objective of this prompt?', 'Test objective')
            ->expectsQuestion('What key details should be included?', 'Test details')
            ->expectsQuestion('Are there any specific formatting requirements?', 'Test formatting')
            ->expectsQuestion('Who is the target audience for this prompt?', 'Test audience')
            ->expectsQuestion('Any additional notes or constraints?', 'Test notes')
            ->assertExitCode(0);

        // Check if file exists
        $filePath = base_path("app/code/prompts/frontend/Test Task.md");
        $this->assertTrue(File::exists($filePath));
        
        // Clean up
        if (File::exists($filePath)) {
            File::delete($filePath);
        }
});

it('can execute a command without arguments', function () {
    // Test without arguments (will ask for name)
    $this->artisan('devteam')
        ->expectsQuestion('What is the name of the prompt?', 'test-prompt-2')
        ->expectsQuestion('What is the objective of this prompt?', 'Test objective')
        ->expectsQuestion('What key details should be included?', 'Test details')
        ->expectsQuestion('Are there any specific formatting requirements?', 'Test formatting')
        ->expectsQuestion('Who is the target audience for this prompt?', 'Test audience')
        ->expectsQuestion('Any additional notes or constraints?', 'Test notes')
        ->expectsOutput('Prompt saved successfully: ' . base_path('app/code/prompts/test-prompt-2.md'))
        ->assertExitCode(0);
});


