<?php

namespace mbscholars\Devteam\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Finder\Finder;

class LaravelAppSummary extends Command
{
    protected $signature = 'devteam:backend-summary {--output= : Path to output file}';

    protected $description = 'Provides a technical summary of the controllers, models, packages used in the app';

    protected $ignoredDirectories = [];

    protected $maxFileSize;

    protected $maxScanDepth;

    protected $summary = [];

    public function __construct()
    {
        parent::__construct();

        // Load configuration values
        $this->ignoredDirectories = config('devteam.summary.ignored_directories', [
            'migrations',
            'resources/views',
            'node_modules',
            'vendor',
            'storage',
            'bootstrap/cache',
            'public',
            'tests',
        ]);

        $this->maxFileSize = config('devteam.summary.max_file_size', 500);
        $this->maxScanDepth = config('devteam.summary.max_scan_depth', 5);
    }

    public function handle()
    {
        $this->info('Generating application summary...');

        $this->scanComposerPackages();
        $this->scanAppDirectory();
        $this->scanRoutes();

        $outputPath = $this->option('output') ?? 'backend-summary.json';
        $outputPath = 'devteam/contexts/'.$outputPath;
        // Ensure directory exists
        $directory = dirname($outputPath);
        if (! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        File::put($outputPath, json_encode($this->summary, JSON_PRETTY_PRINT));

        $this->info("Summary generated successfully at {$outputPath}");

        return Command::SUCCESS;
    }

    protected function scanComposerPackages()
    {
        $this->info('Scanning composer packages...');

        if (File::exists(base_path('composer.json'))) {
            $composerJson = json_decode(File::get(base_path('composer.json')), true);

            $this->summary['packages'] = [
                'require' => $composerJson['require'] ?? [],
                'require-dev' => $composerJson['require-dev'] ?? [],
            ];
        }
    }

    protected function scanAppDirectory()
    {
        $this->info('Scanning application directories...');

        $this->summary['components'] = [
            'controllers' => $this->scanControllers(),
            'models' => $this->scanModels(),
            'commands' => $this->scanCommands(),
            'providers' => $this->scanProviders(),
            'middleware' => $this->scanMiddleware(),
            'jobs' => $this->scanJobs(),
            'events' => $this->scanEvents(),
            'listeners' => $this->scanListeners(),
            'policies' => $this->scanPolicies(),
        ];

        // Scan additional directories from config
        $additionalDirectories = config('devteam.summary.backend_scan_directories', []);
        foreach ($additionalDirectories as $directory) {
            $dirName = basename($directory);
            $this->summary['components'][$dirName] = $this->scanDirectory($directory, function ($file) use ($dirName) {
                return $this->analyzePhpFile($file, ucfirst(rtrim($dirName, 's')));
            });
        }
    }

    protected function scanControllers()
    {
        return $this->scanDirectory('app/Http/Controllers', function ($file) {
            return $this->analyzePhpFile($file, 'Controller');
        });
    }

    protected function scanModels()
    {
        return $this->scanDirectory('app/Models', function ($file) {
            $info = $this->analyzePhpFile($file, 'Model');

            // Add table information if available
            if (! empty($info)) {
                $className = $info['class'];
                try {
                    if (class_exists($className)) {
                        $model = new $className;
                        $info['table'] = $model->getTable();

                        // Get relationships
                        $info['relationships'] = $this->getModelRelationships($model);

                        // Get fillable attributes
                        $info['fillable'] = $model->getFillable();
                    }
                } catch (\Exception $e) {
                    // Skip if model can't be instantiated
                }
            }

            return $info;
        });
    }

    protected function scanCommands()
    {
        return $this->scanDirectory('app/Console/Commands', function ($file) {
            return $this->analyzePhpFile($file, 'Command');
        });
    }

    protected function scanProviders()
    {
        return $this->scanDirectory('app/Providers', function ($file) {
            return $this->analyzePhpFile($file, 'ServiceProvider');
        });
    }

    protected function scanMiddleware()
    {
        return $this->scanDirectory('app/Http/Middleware', function ($file) {
            return $this->analyzePhpFile($file, 'Middleware');
        });
    }

    protected function scanJobs()
    {
        return $this->scanDirectory('app/Jobs', function ($file) {
            return $this->analyzePhpFile($file, 'Job');
        });
    }

    protected function scanEvents()
    {
        return $this->scanDirectory('app/Events', function ($file) {
            return $this->analyzePhpFile($file, 'Event');
        });
    }

    protected function scanListeners()
    {
        return $this->scanDirectory('app/Listeners', function ($file) {
            return $this->analyzePhpFile($file, 'Listener');
        });
    }

    protected function scanPolicies()
    {
        return $this->scanDirectory('app/Policies', function ($file) {
            return $this->analyzePhpFile($file, 'Policy');
        });
    }

    protected function scanRoutes()
    {
        if (! config('devteam.ai_context.include_routes', true)) {
            return;
        }

        $this->info('Scanning routes...');

        $routeFiles = [
            'web' => base_path('routes/web.php'),
            'api' => base_path('routes/api.php'),
        ];

        $routes = [];

        foreach ($routeFiles as $type => $path) {
            if (File::exists($path)) {
                $content = File::get($path);
                $routes[$type] = $this->extractRouteInfo($content);
            }
        }

        $this->summary['routes'] = $routes;
    }

    protected function scanDatabaseSchema()
    {
        $this->info('Scanning database schema...');

        try {
            $tables = [];
            $tableNames = Schema::getConnection()->getDoctrineSchemaManager()->listTableNames();

            foreach ($tableNames as $tableName) {
                $columns = Schema::getColumnListing($tableName);
                $columnDetails = [];

                foreach ($columns as $column) {
                    $type = Schema::getColumnType($tableName, $column);
                    $columnDetails[$column] = $type;
                }

                $tables[$tableName] = $columnDetails;
            }

            $this->summary['database'] = [
                'tables' => $tables,
            ];
        } catch (\Exception $e) {
            $this->warn('Could not scan database schema: '.$e->getMessage());
        }
    }

    protected function extractRouteInfo($content)
    {
        $routeInfo = [];
        $pattern = '/Route::(get|post|put|patch|delete|options|any)\s*\(\s*[\'"]([^\'"]+)[\'"]\s*,\s*(?:\[[^\]]+\]|[^)]+)\)/';

        preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $routeInfo[] = [
                'method' => strtoupper($match[1]),
                'uri' => $match[2],
            ];
        }

        return $routeInfo;
    }

    protected function scanDirectory($directory, callable $analyzer)
    {
        $path = base_path($directory);
        $results = [];

        if (! File::isDirectory($path)) {
            return $results;
        }

        $finder = new Finder;
        $finder->files()->in($path)->name('*.php');

        // Apply max depth if configured
        if ($this->maxScanDepth > 0) {
            $finder->depth("< {$this->maxScanDepth}");
        }

        // Skip ignored directories
        foreach ($this->ignoredDirectories as $ignoredDir) {
            $ignoredPath = base_path($ignoredDir);
            if (File::isDirectory($ignoredPath)) {
                $finder->exclude($ignoredDir);
            }
        }

        // Apply max file size if configured
        if ($this->maxFileSize > 0) {
            $maxSizeInBytes = $this->maxFileSize * 1024; // Convert KB to bytes
            $finder->size("< {$maxSizeInBytes}");
        }

        foreach ($finder as $file) {
            $info = $analyzer($file);
            if (! empty($info)) {
                $results[] = $info;
            }
        }

        return $results;
    }

    protected function analyzePhpFile($file, $type)
    {
        $content = $file->getContents();
        $relativePath = $this->getRelativePath($file->getRealPath());

        // Extract namespace
        $namespace = $this->extractNamespace($content);

        // Extract class name
        $className = $this->extractClassName($content);

        if (empty($className)) {
            return null;
        }

        $fullyQualifiedClassName = $namespace ? "{$namespace}\\{$className}" : $className;

        // Extract methods
        $methods = $this->extractMethods($content);

        // Extract class docblock
        $docblock = $this->extractDocblock($content);

        return [
            'type' => $type,
            'class' => $fullyQualifiedClassName,
            'file' => $relativePath,
            'methods' => $methods,
            'docblock' => $docblock,
        ];
    }

    protected function extractNamespace($content)
    {
        if (preg_match('/namespace\s+([^;]+);/', $content, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    protected function extractClassName($content)
    {
        if (preg_match('/class\s+(\w+)(?:\s+extends|\s+implements|\s*\{|$)/', $content, $matches)) {
            return $matches[1];
        }

        return null;
    }

    protected function extractMethods($content)
    {
        $methods = [];
        $pattern = '/(?:\/\*\*(?:(?!\*\/).)*\*\/\s*)?(?:public|protected|private)\s+function\s+(\w+)\s*\([^)]*\)/s';

        preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $methodName = $match[1];
            $docblock = '';

            // Extract method docblock if it exists
            if (preg_match('/\/\*\*((?:(?!\*\/).)*)\*\/\s*(?:public|protected|private)\s+function\s+'.preg_quote($methodName).'\s*\(/s', $content, $docMatches)) {
                $docblock = $this->cleanDocblock($docMatches[1]);
            }

            $methods[] = [
                'name' => $methodName,
                'docblock' => $docblock,
            ];
        }

        return $methods;
    }

    protected function extractDocblock($content)
    {
        if (preg_match('/\/\*\*((?:(?!\*\/).)*)\*\/\s*class\s+/s', $content, $matches)) {
            return $this->cleanDocblock($matches[1]);
        }

        return '';
    }

    protected function cleanDocblock($docblock)
    {
        // Remove asterisks and normalize whitespace
        $lines = explode("\n", $docblock);
        $cleaned = [];

        foreach ($lines as $line) {
            $line = preg_replace('/^\s*\*\s?/', '', $line);
            $cleaned[] = trim($line);
        }

        return implode("\n", array_filter($cleaned));
    }

    protected function getRelativePath($path)
    {
        return str_replace(base_path().'/', '', $path);
    }

    protected function getModelRelationships($model)
    {
        $relationships = [];
        $methods = get_class_methods($model);

        foreach ($methods as $method) {
            try {
                $reflection = new \ReflectionMethod($model, $method);
                $code = $this->getMethodBody($reflection);

                // Check if method contains relationship definitions
                if (preg_match('/return\s+\$this->(hasOne|hasMany|belongsTo|belongsToMany|morphTo|morphMany|morphToMany|morphedByMany)\(/', $code)) {
                    $relationships[] = $method;
                }
            } catch (\Exception $e) {
                // Skip if method can't be analyzed
            }
        }

        return $relationships;
    }

    protected function getMethodBody(\ReflectionMethod $method)
    {
        $filename = $method->getFileName();
        $start_line = $method->getStartLine() - 1;
        $end_line = $method->getEndLine();
        $length = $end_line - $start_line;

        $source = file($filename);
        $body = implode('', array_slice($source, $start_line, $length));

        return $body;
    }
}
