<?php

namespace mbscholars\Devteam\Tests\Commands;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

it('can dump database schema to a file', function () {
    // Create a temporary test table
    Schema::create('test_dump_table', function ($table) {
        $table->id();
        $table->string('name');
        $table->text('description')->nullable();
        $table->timestamps();
    });

    // Create a related table with foreign key
    Schema::create('test_dump_related', function ($table) {
        $table->id();
        $table->foreignId('test_dump_table_id')->constrained('test_dump_table');
        $table->string('data');
        $table->timestamps();
    });

    // Set up test file path
    $filePath = base_path('test_schema_dump.txt');

    // Execute the command
    $this->artisan('schema:dump', ['file' => $filePath])
        ->assertExitCode(0);

    // Check if file exists
    expect(File::exists($filePath))->toBeTrue();

    // Check content
    $content = File::get($filePath);
    expect($content)->toContain('Database Schema Dump');
    expect($content)->toContain('Table: test_dump_table');
    expect($content)->toContain('id - bigint');
    expect($content)->toContain('name - varchar');
    expect($content)->toContain('description - text');
    expect($content)->toContain('Table: test_dump_related');
    expect($content)->toContain('test_dump_table_id - bigint');
    expect($content)->toContain('Foreign Keys:');
    expect($content)->toContain('test_dump_table_id -> test_dump_table(id)');

    // Clean up
    Schema::dropIfExists('test_dump_related');
    Schema::dropIfExists('test_dump_table');
    if (File::exists($filePath)) {
        File::delete($filePath);
    }
});

it('handles empty database gracefully', function () {
    // Drop all tables for this test
    $tables = collect(DB::select('SHOW TABLES'))->map(function ($table) {
        return array_values(get_object_vars($table))[0];
    })->filter(function ($table) {
        // Skip migration tables to avoid breaking the test environment
        return ! in_array($table, ['migrations', 'failed_jobs', 'password_reset_tokens']);
    })->toArray();

    // Store original tables to restore later
    $originalTables = $tables;

    // Drop tables temporarily
    foreach ($tables as $table) {
        Schema::dropIfExists($table);
    }

    // Set up test file path
    $filePath = base_path('empty_schema_dump.txt');

    // Execute the command
    $this->artisan('schema:dump', ['file' => $filePath])
        ->assertExitCode(0);

    // Check if file exists
    expect(File::exists($filePath))->toBeTrue();

    // Check content - should still have header
    $content = File::get($filePath);
    expect($content)->toContain('Database Schema Dump');

    // Clean up
    if (File::exists($filePath)) {
        File::delete($filePath);
    }

    // Recreate original tables if needed
    // This would require storing the original schema which is beyond the scope of this test
});

it('creates directory if it does not exist', function () {
    // Set up test file path in a non-existent directory
    $dirPath = base_path('test_schema_dir');
    $filePath = $dirPath.'/schema_dump.txt';

    // Remove directory if it exists
    if (File::isDirectory($dirPath)) {
        File::deleteDirectory($dirPath);
    }

    // Create a test table
    Schema::create('test_dump_dir_table', function ($table) {
        $table->id();
        $table->string('name');
        $table->timestamps();
    });

    // Execute the command
    $this->artisan('schema:dump', ['file' => $filePath])
        ->assertExitCode(0);

    // Check if directory and file exist
    expect(File::isDirectory($dirPath))->toBeTrue();
    expect(File::exists($filePath))->toBeTrue();

    // Clean up
    Schema::dropIfExists('test_dump_dir_table');
    if (File::isDirectory($dirPath)) {
        File::deleteDirectory($dirPath);
    }
});
