<?php

namespace mbscholars\Devteam\Tests\Commands;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

it('can generate application summary', function () {
    // Create a test model file
    $modelDir = app_path('Models');
    File::ensureDirectoryExists($modelDir);
    
    $modelContent = <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Test model for summary command
 */
class TestModel extends Model
{
    protected $table = 'test_models';
    
    protected $fillable = ['name', 'description'];
    
    public function testRelation()
    {
        return $this->hasMany(RelatedModel::class);
    }
}
PHP;
    
    File::put($modelDir . '/TestModel.php', $modelContent);
    
    // Create a test controller
    $controllerDir = app_path('Http/Controllers');
    File::ensureDirectoryExists($controllerDir);
    
    $controllerContent = <<<'PHP'
<?php

namespace App\Http\Controllers;

use App\Models\TestModel;
use Illuminate\Http\Request;

/**
 * Test controller for summary command
 */
class TestController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return TestModel::all();
    }
    
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        return TestModel::create($request->all());
    }
}
PHP;
    
    File::put($controllerDir . '/TestController.php', $controllerContent);
    
    // Create a test table
    Schema::create('test_models', function ($table) {
        $table->id();
        $table->string('name');
        $table->text('description')->nullable();
        $table->timestamps();
    });
    
    // Set up test output path
    $outputPath = base_path('test_app_summary.json');
    
    // Execute the command
    $this->artisan('app:summary', ['--output' => $outputPath])
        ->assertExitCode(0);

    // Check if file exists
    expect(File::exists($outputPath))->toBeTrue();
    
    // Check content
    $content = json_decode(File::get($outputPath), true);
    expect($content)->toBeArray();
    
    // Check for components
    expect($content)->toHaveKey('components');
    
    // Check for models
    $foundTestModel = false;
    if (isset($content['components']['models'])) {
        foreach ($content['components']['models'] as $model) {
            if (isset($model['class']) && $model['class'] === 'App\\Models\\TestModel') {
                $foundTestModel = true;
                expect($model)->toHaveKey('docblock');
                expect($model['docblock'])->toContain('Test model for summary command');
                expect($model)->toHaveKey('methods');
                
                // Check for method
                $foundMethod = false;
                foreach ($model['methods'] as $method) {
                    if ($method['name'] === 'testRelation') {
                        $foundMethod = true;
                        break;
                    }
                }
                expect($foundMethod)->toBeTrue();
                
                break;
            }
        }
    }
    expect($foundTestModel)->toBeTrue();
    
    // Check for controllers
    $foundTestController = false;
    if (isset($content['components']['controllers'])) {
        foreach ($content['components']['controllers'] as $controller) {
            if (isset($controller['class']) && $controller['class'] === 'App\\Http\\Controllers\\TestController') {
                $foundTestController = true;
                expect($controller)->toHaveKey('docblock');
                expect($controller['docblock'])->toContain('Test controller for summary command');
                
                // Check for methods
                $foundMethods = [
                    'index' => false,
                    'store' => false
                ];
                
                foreach ($controller['methods'] as $method) {
                    if (isset($foundMethods[$method['name']])) {
                        $foundMethods[$method['name']] = true;
                    }
                }
                
                expect($foundMethods['index'])->toBeTrue();
                expect($foundMethods['store'])->toBeTrue();
                
                break;
            }
        }
    }
    expect($foundTestController)->toBeTrue();
    
    // Check for database schema
    if (isset($content['database']) && isset($content['database']['tables'])) {
        expect($content['database']['tables'])->toHaveKey('test_models');
        expect($content['database']['tables']['test_models'])->toHaveKey('id');
        expect($content['database']['tables']['test_models'])->toHaveKey('name');
        expect($content['database']['tables']['test_models'])->toHaveKey('description');
    }
    
    // Clean up
    Schema::dropIfExists('test_models');
    if (File::exists($modelDir . '/TestModel.php')) {
        File::delete($modelDir . '/TestModel.php');
    }
    if (File::exists($controllerDir . '/TestController.php')) {
        File::delete($controllerDir . '/TestController.php');
    }
    if (File::exists($outputPath)) {
        File::delete($outputPath);
    }
});

it('handles missing directories gracefully', function () {
    // Temporarily rename directories to simulate missing directories
    $modelDir = app_path('Models');
    $tempModelDir = app_path('Models_temp');
    
    if (File::isDirectory($modelDir)) {
        File::moveDirectory($modelDir, $tempModelDir);
    }
    
    // Set up test output path
    $outputPath = base_path('test_missing_dirs_summary.json');
    
    // Execute the command
    $this->artisan('app:summary', ['--output' => $outputPath])
        ->assertExitCode(0);

    // Check if file exists
    expect(File::exists($outputPath))->toBeTrue();
    
    // Check content
    $content = json_decode(File::get($outputPath), true);
    expect($content)->toBeArray();
    
    // Restore directories
    if (File::isDirectory($tempModelDir)) {
        File::moveDirectory($tempModelDir, $modelDir);
    }
    
    // Clean up
    if (File::exists($outputPath)) {
        File::delete($outputPath);
    }
});

it('respects configuration for ignored directories', function () {
    // Create a test file in a directory that should be ignored
    $ignoredDir = base_path('storage/logs');
    File::ensureDirectoryExists($ignoredDir);
    
    $ignoredFile = $ignoredDir . '/test_ignored.php';
    File::put($ignoredFile, '<?php class IgnoredClass {}');
    
    // Set up test output path
    $outputPath = base_path('test_ignored_dirs_summary.json');
    
    // Execute the command
    $this->artisan('app:summary', ['--output' => $outputPath])
        ->assertExitCode(0);

    // Check if file exists
    expect(File::exists($outputPath))->toBeTrue();
    
    // Check content - the ignored file should not be included
    $content = json_decode(File::get($outputPath), true);
    $foundIgnoredClass = false;
    
    // Recursively search for the ignored class in the summary
    $searchForClass = function ($array) use (&$searchForClass, &$foundIgnoredClass) {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $searchForClass($value);
            } elseif (is_string($value) && strpos($value, 'IgnoredClass') !== false) {
                $foundIgnoredClass = true;
            }
        }
    };
    
    $searchForClass($content);
    expect($foundIgnoredClass)->toBeFalse();
    
    // Clean up
    if (File::exists($ignoredFile)) {
        File::delete($ignoredFile);
    }
    if (File::exists($outputPath)) {
        File::delete($outputPath);
    }
}); 