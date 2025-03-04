<?php

namespace mbscholars\Devteam\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\Finder;

class LaravelFrontEndSummary extends Command
{
    protected $signature = 'app:frontend-summary {--output=frontend-summary.json : Path to output file}';
    protected $description = 'Provides a technical summary of Vue components, JS utilities, and front-end assets';

    protected $ignoredDirectories = [
        'node_modules',
        'vendor',
        'storage',
        'bootstrap/cache',
    ];

    protected $summary = [];

    public function handle()
    {
        $this->info('Generating frontend summary...');
        
        $this->scanNpmPackages();
        $this->scanVueComponents();
        $this->scanJsUtilities();
        $this->scanCssAssets();
        $this->scanViteConfig();
        
        $outputPath = $this->option('output');
        File::put($outputPath, json_encode($this->summary, JSON_PRETTY_PRINT));
        
        $this->info("Frontend summary generated successfully at {$outputPath}");
        
        return Command::SUCCESS;
    }
    
    protected function scanNpmPackages()
    {
        $this->info('Scanning npm packages...');
        
        if (File::exists(base_path('package.json'))) {
            $packageJson = json_decode(File::get(base_path('package.json')), true);
            
            $this->summary['packages'] = [
                'dependencies' => $packageJson['dependencies'] ?? [],
                'devDependencies' => $packageJson['devDependencies'] ?? [],
            ];
            
            // Check for Vue version
            if (isset($packageJson['dependencies']['vue'])) {
                $this->summary['vueVersion'] = $packageJson['dependencies']['vue'];
            }
        }
    }
    
    protected function scanVueComponents()
    {
        $this->info('Scanning Vue components...');
        
        $componentsDirectories = [
            'resources/js/components',
            'resources/js/Pages',
            'resources/js/Layouts',
        ];
        
        $components = [];
        
        foreach ($componentsDirectories as $directory) {
            $path = base_path($directory);
            
            if (!File::isDirectory($path)) {
                continue;
            }
            
            $finder = new Finder();
            $finder->files()->in($path)->name('*.vue');
            
            foreach ($finder as $file) {
                $components[] = $this->analyzeVueComponent($file);
            }
        }
        
        $this->summary['components'] = $components;
    }
    
    protected function analyzeVueComponent($file)
    {
        $content = $file->getContents();
        $relativePath = $this->getRelativePath($file->getRealPath());
        $componentName = $file->getBasename('.vue');
        
        // Extract script section
        $scriptContent = $this->extractVueSection($content, 'script');
        
        // Check if using Composition API
        $isCompositionApi = $this->isUsingCompositionApi($scriptContent);
        
        // Extract imports
        $imports = $this->extractImports($scriptContent);
        
        // Extract props
        $props = $this->extractProps($scriptContent);
        
        // Extract emits
        $emits = $this->extractEmits($scriptContent);
        
        // Extract composables/hooks
        $composables = $this->extractComposables($scriptContent);
        
        // Extract template section (just check if it exists)
        $hasTemplate = $this->hasVueSection($content, 'template');
        
        // Extract style section
        $styleInfo = $this->extractStyleInfo($content);
        
        return [
            'name' => $componentName,
            'file' => $relativePath,
            'isCompositionApi' => $isCompositionApi,
            'imports' => $imports,
            'props' => $props,
            'emits' => $emits,
            'composables' => $composables,
            'hasTemplate' => $hasTemplate,
            'style' => $styleInfo,
        ];
    }
    
    protected function extractVueSection($content, $section)
    {
        $pattern = "/<$section(?:\s+[^>]*)?>(.+?)<\/$section>/s";
        if (preg_match($pattern, $content, $matches)) {
            return $matches[1];
        }
        
        return '';
    }
    
    protected function hasVueSection($content, $section)
    {
        $pattern = "/<$section(?:\s+[^>]*)?>(.+?)<\/$section>/s";
        return preg_match($pattern, $content) === 1;
    }
    
    protected function isUsingCompositionApi($scriptContent)
    {
        // Check for defineComponent, ref, reactive, computed, etc.
        $compositionApiPatterns = [
            '/defineComponent\s*\(/i',
            '/setup\s*\(/i',
            '/ref\s*\(/i',
            '/reactive\s*\(/i',
            '/computed\s*\(/i',
            '/watch\s*\(/i',
            '/onMounted\s*\(/i',
            '/defineProps\s*\(/i',
            '/defineEmits\s*\(/i',
        ];
        
        foreach ($compositionApiPatterns as $pattern) {
            if (preg_match($pattern, $scriptContent)) {
                return true;
            }
        }
        
        return false;
    }
    
    protected function extractImports($scriptContent)
    {
        $imports = [];
        $pattern = '/import\s+(?:{([^}]+)}|([^\s;]+))\s+from\s+[\'"]([^\'"]+)[\'"]/';
        
        preg_match_all($pattern, $scriptContent, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            if (!empty($match[1])) {
                // Named imports
                $namedImports = array_map('trim', explode(',', $match[1]));
                $imports[] = [
                    'type' => 'named',
                    'imports' => $namedImports,
                    'from' => $match[3],
                ];
            } else {
                // Default import
                $imports[] = [
                    'type' => 'default',
                    'import' => $match[2],
                    'from' => $match[3],
                ];
            }
        }
        
        return $imports;
    }
    
    protected function extractProps($scriptContent)
    {
        $props = [];
        
        // Check for defineProps
        $definePropsPattern = '/defineProps\s*\(\s*({[^}]+}|\[[^\]]+\])\s*\)/s';
        if (preg_match($definePropsPattern, $scriptContent, $matches)) {
            $propsDefinition = $matches[1];
            
            // Extract prop names from object notation
            if (substr($propsDefinition, 0, 1) === '{') {
                preg_match_all('/(\w+)\s*:/s', $propsDefinition, $propMatches);
                $props = $propMatches[1] ?? [];
            }
            // Extract prop names from array notation
            else if (substr($propsDefinition, 0, 1) === '[') {
                preg_match_all('/[\'"](\w+)[\'"]/s', $propsDefinition, $propMatches);
                $props = $propMatches[1] ?? [];
            }
        }
        
        // Check for props option in defineComponent
        $propsOptionPattern = '/props\s*:\s*({[^}]+}|\[[^\]]+\])/s';
        if (preg_match($propsOptionPattern, $scriptContent, $matches)) {
            $propsDefinition = $matches[1];
            
            // Extract prop names from object notation
            if (substr($propsDefinition, 0, 1) === '{') {
                preg_match_all('/(\w+)\s*:/s', $propsDefinition, $propMatches);
                $props = array_merge($props, $propMatches[1] ?? []);
            }
            // Extract prop names from array notation
            else if (substr($propsDefinition, 0, 1) === '[') {
                preg_match_all('/[\'"](\w+)[\'"]/s', $propsDefinition, $propMatches);
                $props = array_merge($props, $propMatches[1] ?? []);
            }
        }
        
        return array_unique($props);
    }
    
    protected function extractEmits($scriptContent)
    {
        $emits = [];
        
        // Check for defineEmits
        $defineEmitsPattern = '/defineEmits\s*\(\s*(\[[^\]]+\]|{[^}]+})\s*\)/s';
        if (preg_match($defineEmitsPattern, $scriptContent, $matches)) {
            $emitsDefinition = $matches[1];
            
            // Extract emit names from array notation
            if (substr($emitsDefinition, 0, 1) === '[') {
                preg_match_all('/[\'"](\w+)[\'"]/s', $emitsDefinition, $emitMatches);
                $emits = $emitMatches[1] ?? [];
            }
            // Extract emit names from object notation
            else if (substr($emitsDefinition, 0, 1) === '{') {
                preg_match_all('/(\w+)\s*:/s', $emitsDefinition, $emitMatches);
                $emits = $emitMatches[1] ?? [];
            }
        }
        
        // Check for emits option in defineComponent
        $emitsOptionPattern = '/emits\s*:\s*(\[[^\]]+\]|{[^}]+})/s';
        if (preg_match($emitsOptionPattern, $scriptContent, $matches)) {
            $emitsDefinition = $matches[1];
            
            // Extract emit names from array notation
            if (substr($emitsDefinition, 0, 1) === '[') {
                preg_match_all('/[\'"](\w+)[\'"]/s', $emitsDefinition, $emitMatches);
                $emits = array_merge($emits, $emitMatches[1] ?? []);
            }
            // Extract emit names from object notation
            else if (substr($emitsDefinition, 0, 1) === '{') {
                preg_match_all('/(\w+)\s*:/s', $emitsDefinition, $emitMatches);
                $emits = array_merge($emits, $emitMatches[1] ?? []);
            }
        }
        
        return array_unique($emits);
    }
    
    protected function extractComposables($scriptContent)
    {
        $composables = [];
        $composablePatterns = [
            'ref' => '/const\s+(\w+)\s*=\s*ref\s*\(/i',
            'reactive' => '/const\s+(\w+)\s*=\s*reactive\s*\(/i',
            'computed' => '/const\s+(\w+)\s*=\s*computed\s*\(/i',
            'watch' => '/watch\s*\(\s*(\w+)/i',
            'lifecycle' => '/on(Mounted|BeforeMount|BeforeUnmount|Unmounted|Activated|Deactivated|BeforeUpdate|Updated|ErrorCaptured)\s*\(/i',
            'provide/inject' => '/(provide|inject)\s*\(\s*[\'"](\w+)[\'"]/i',
        ];
        
        foreach ($composablePatterns as $type => $pattern) {
            preg_match_all($pattern, $scriptContent, $matches);
            
            if (!empty($matches[1])) {
                $composables[$type] = array_unique($matches[1]);
            }
        }
        
        // Check for custom composables (useX functions)
        preg_match_all('/const\s+(\w+)\s*=\s*use(\w+)\s*\(/i', $scriptContent, $customMatches);
        if (!empty($customMatches[0])) {
            $customComposables = [];
            for ($i = 0; $i < count($customMatches[0]); $i++) {
                $customComposables[] = [
                    'variable' => $customMatches[1][$i],
                    'composable' => 'use' . $customMatches[2][$i],
                ];
            }
            $composables['custom'] = $customComposables;
        }
        
        return $composables;
    }
    
    protected function extractStyleInfo($content)
    {
        $styleInfo = [
            'hasStyle' => false,
            'scoped' => false,
            'lang' => null,
        ];
        
        $stylePattern = '/<style(\s+[^>]*)?>(.+?)<\/style>/s';
        if (preg_match($stylePattern, $content, $matches)) {
            $styleInfo['hasStyle'] = true;
            
            $attributes = $matches[1] ?? '';
            $styleInfo['scoped'] = strpos($attributes, 'scoped') !== false;
            
            if (preg_match('/lang=[\'"](scss|sass|less|stylus)[\'"]/i', $attributes, $langMatches)) {
                $styleInfo['lang'] = $langMatches[1];
            }
        }
        
        return $styleInfo;
    }
    
    protected function scanJsUtilities()
    {
        $this->info('Scanning JS utilities...');
        
        $utilitiesDirectories = [
            'resources/js/utils',
            'resources/js/helpers',
            'resources/js/composables',
            'resources/js/hooks',
            'resources/js/stores',
        ];
        
        $utilities = [];
        
        foreach ($utilitiesDirectories as $directory) {
            $path = base_path($directory);
            
            if (!File::isDirectory($path)) {
                continue;
            }
            
            $finder = new Finder();
            $finder->files()->in($path)->name('*.js')->name('*.ts');
            
            foreach ($finder as $file) {
                $utilities[] = $this->analyzeJsUtility($file);
            }
        }
        
        $this->summary['utilities'] = $utilities;
    }
    
    protected function analyzeJsUtility($file)
    {
        $content = $file->getContents();
        $relativePath = $this->getRelativePath($file->getRealPath());
        $fileName = $file->getBasename('.' . $file->getExtension());
        
        // Extract exports
        $exports = $this->extractExports($content);
        
        // Extract imports
        $imports = $this->extractImports($content);
        
        // Check if it's a composable
        $isComposable = strpos($fileName, 'use') === 0 && ctype_upper($fileName[3]);
        
        // Check if it's a Pinia store
        $isPiniaStore = $this->isPiniaStore($content);
        
        return [
            'name' => $fileName,
            'file' => $relativePath,
            'isComposable' => $isComposable,
            'isPiniaStore' => $isPiniaStore,
            'imports' => $imports,
            'exports' => $exports,
        ];
    }
    
    protected function extractExports($content)
    {
        $exports = [];
        
        // Default export
        $defaultExportPattern = '/export\s+default\s+(?:function\s+(\w+)|(\w+)|{)/';
        if (preg_match($defaultExportPattern, $content, $matches)) {
            $exports['default'] = !empty($matches[1]) ? $matches[1] : (!empty($matches[2]) ? $matches[2] : 'anonymous');
        }
        
        // Named exports
        $namedExportPattern = '/export\s+(?:const|let|var|function)\s+(\w+)/';
        preg_match_all($namedExportPattern, $content, $namedMatches);
        
        if (!empty($namedMatches[1])) {
            $exports['named'] = $namedMatches[1];
        }
        
        return $exports;
    }
    
    protected function isPiniaStore($content)
    {
        return strpos($content, 'defineStore') !== false;
    }
    
    protected function scanCssAssets()
    {
        $this->info('Scanning CSS assets...');
        
        $cssDirectories = [
            'resources/css',
            'resources/sass',
            'resources/scss',
        ];
        
        $cssAssets = [];
        
        foreach ($cssDirectories as $directory) {
            $path = base_path($directory);
            
            if (!File::isDirectory($path)) {
                continue;
            }
            
            $finder = new Finder();
            $finder->files()->in($path)->name('*.css')->name('*.scss')->name('*.sass')->name('*.less');
            
            foreach ($finder as $file) {
                $cssAssets[] = [
                    'name' => $file->getBasename(),
                    'file' => $this->getRelativePath($file->getRealPath()),
                    'type' => $file->getExtension(),
                ];
            }
        }
        
        $this->summary['cssAssets'] = $cssAssets;
    }
    
    protected function scanViteConfig()
    {
        $this->info('Scanning Vite configuration...');
        
        $viteConfigFiles = [
            'vite.config.js',
            'vite.config.ts',
        ];
        
        foreach ($viteConfigFiles as $configFile) {
            $path = base_path($configFile);
            
            if (File::exists($path)) {
                $content = File::get($path);
                
                // Check for plugins
                $plugins = $this->extractVitePlugins($content);
                
                $this->summary['viteConfig'] = [
                    'file' => $configFile,
                    'plugins' => $plugins,
                ];
                
                break;
            }
        }
    }
    
    protected function extractVitePlugins($content)
    {
        $plugins = [];
        $pluginPattern = '/(?:plugin|use)\s*\(\s*(\w+)/';
        
        preg_match_all($pluginPattern, $content, $matches);
        
        if (!empty($matches[1])) {
            $plugins = $matches[1];
        }
        
        return $plugins;
    }
    
    protected function getRelativePath($path)
    {
        return str_replace(base_path() . '/', '', $path);
    }
} 