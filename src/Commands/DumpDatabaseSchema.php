<?php

namespace mbscholars\Devteam\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DumpDatabaseSchema extends Command
{
    protected $signature = 'schema:dump {file}'; // Pass the output file path
    protected $description = 'Dumps the Laravel database schema to a text file';

    public function handle()
    {
        $filePath = $this->argument('file');

        $tables = DB::select('SHOW TABLES');
        $databaseName = config('database.connections.mysql.database');
        $tableKey = "Tables_in_{$databaseName}";
        $output = "Database Schema Dump\n====================\n\n";

        foreach ($tables as $table) {
            $tableName = $table->$tableKey;
            $output .= "Table: $tableName\n";
            $output .= str_repeat('-', strlen("Table: $tableName")) . "\n";

            // Get Columns
            $columns = DB::select("SHOW COLUMNS FROM `$tableName`");
            foreach ($columns as $column) {
                $output .= "{$column->Field} - {$column->Type}";
                if ($column->Null === "NO") {
                    $output .= " (NOT NULL)";
                }
                if ($column->Key) {
                    $output .= " (KEY: {$column->Key})";
                }
                if ($column->Default !== null) {
                    $output .= " (DEFAULT: {$column->Default})";
                }
                $output .= "\n";
            }

            // Get Foreign Keys
            $foreignKeys = DB::select("
                SELECT COLUMN_NAME, CONSTRAINT_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME 
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                WHERE TABLE_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL", [$tableName]);

            if (!empty($foreignKeys)) {
                $output .= "\nForeign Keys:\n";
                foreach ($foreignKeys as $fk) {
                    $output .= "  - {$fk->COLUMN_NAME} -> {$fk->REFERENCED_TABLE_NAME}({$fk->REFERENCED_COLUMN_NAME})\n";
                }
            }

            $output .= "\n\n";
        }

        file_put_contents($filePath, $output);
        $this->info("Database schema dumped successfully to: $filePath");
    }
}
