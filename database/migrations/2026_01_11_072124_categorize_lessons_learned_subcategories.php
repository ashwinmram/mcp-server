<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $lessons = DB::table('lessons')
            ->where('category', 'lessons-learned')
            ->get();

        foreach ($lessons as $lesson) {
            $subcategory = $this->determineSubcategory($lesson->content, $lesson->tags);

            DB::table('lessons')
                ->where('id', $lesson->id)
                ->update(['subcategory' => $subcategory]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('lessons')
            ->where('category', 'lessons-learned')
            ->update(['subcategory' => null]);
    }

    /**
     * Determine subcategory based on content and tags.
     */
    private function determineSubcategory(?string $content, ?string $tagsJson): ?string
    {
        if (empty($content)) {
            return null;
        }

        $contentLower = strtolower($content);
        $tags = $tagsJson ? json_decode($tagsJson, true) : [];
        $tagsLower = array_map('strtolower', $tags);

        // Check for explicit categories in JSON content
        $decoded = json_decode($content, true);
        if (is_array($decoded)) {
            // Check for categories array
            if (isset($decoded['categories']) && is_array($decoded['categories'])) {
                $categories = array_map('strtolower', $decoded['categories']);

                // Map explicit categories to subcategories
                if (in_array('component architecture', $categories) ||
                    in_array('component architecture', array_map('strtolower', $decoded['categories']))) {
                    return 'component-architecture';
                }
                if (in_array('testing patterns', $categories) ||
                    in_array('frontend test', $categories) ||
                    in_array('backend test', $categories)) {
                    return 'testing-patterns';
                }
                if (in_array('database & backend', $categories) ||
                    in_array('database', $categories)) {
                    return 'database-backend';
                }
                if (in_array('frontend development', $categories) ||
                    in_array('frontend', $categories)) {
                    return 'frontend-development';
                }
                if (in_array('inertia.js & routing', $categories) ||
                    in_array('inertia', $categories)) {
                    return 'inertia-routing';
                }
                if (in_array('performance & optimization', $categories) ||
                    in_array('performance', $categories)) {
                    return 'performance-optimization';
                }
                if (in_array('error handling & debugging', $categories) ||
                    in_array('error handling', $categories)) {
                    return 'error-handling';
                }
                if (in_array('code quality & maintenance', $categories) ||
                    in_array('code quality', $categories)) {
                    return 'code-quality';
                }
                if (in_array('authentication styling', $categories) ||
                    in_array('role-based access control', $categories) ||
                    in_array('authentication', $categories)) {
                    return 'authentication-authorization';
                }
            }

            // Check for specific topic keys
            if (isset($decoded['category'])) {
                $category = strtolower($decoded['category']);
                if ($category === 'development environment & tooling' ||
                    $category === 'version_control') {
                    return 'development-environment';
                }
            }

            // Check for specific patterns in keys
            if (isset($decoded['inertia_first_architecture']) ||
                isset($decoded['inertia_v2_features'])) {
                return 'inertia-routing';
            }
            if (isset($decoded['foreign_key_constraints']) ||
                isset($decoded['unique_constraints']) ||
                isset($decoded['migration_management']) ||
                isset($decoded['php_variable_scoping']) ||
                isset($decoded['enum_casting_bypass'])) {
                return 'database-backend';
            }
            if (isset($decoded['systematic_test_fixing']) ||
                isset($decoded['frontend_test_patterns']) ||
                isset($decoded['backend_test_patterns']) ||
                isset($decoded['phpunit_12_modernization'])) {
                return 'testing-patterns';
            }
            if (isset($decoded['singleton_pattern']) ||
                isset($decoded['single_responsibility_principle']) ||
                isset($decoded['vue_component_patterns'])) {
                return 'component-architecture';
            }
            if (isset($decoded['vue_development']) ||
                isset($decoded['progressive_loading']) ||
                isset($decoded['visual_feedback']) ||
                isset($decoded['form_optimization']) ||
                isset($decoded['image_upload_guidance'])) {
                return 'frontend-development';
            }
            if (isset($decoded['error_banner_pattern']) ||
                isset($decoded['console_error_resolution']) ||
                isset($decoded['build_error_management']) ||
                isset($decoded['test_failure_debugging'])) {
                return 'error-handling';
            }
            if (isset($decoded['caching_strategies']) ||
                isset($decoded['conditional_testing'])) {
                return 'performance-optimization';
            }
            if (isset($decoded['formatting']) ||
                isset($decoded['documentation']) ||
                isset($decoded['refactoring_safety'])) {
                return 'code-quality';
            }
        }

        // Keyword-based categorization
        $keywords = [
            'component-architecture' => [
                'component', 'vue component', 'single responsibility', 'prop design',
                'parent child', 'component extraction', 'singleton pattern',
            ],
            'database-backend' => [
                'foreign key', 'migration', 'database', 'constraint', 'pivot table',
                'enum casting', 'php variable scoping', 'currency conversion', 'eloquent',
            ],
            'frontend-development' => [
                'vue', 'frontend', 'ui', 'ux', 'visual feedback', 'progressive loading',
                'image upload', 'form optimization', 'localstorage', 'client-side',
            ],
            'inertia-routing' => [
                'inertia', 'router', 'routing', 'deferred props', 'whenvisible',
                'prefetching', 'inertia v2', 'route response',
            ],
            'testing-patterns' => [
                'test', 'testing', 'phpunit', 'pest', 'mock', 'assertion',
                'frontend test', 'backend test', 'test pattern',
            ],
            'error-handling' => [
                'error', 'debugging', 'console error', 'error banner', 'exception',
                'build error', 'test failure',
            ],
            'performance-optimization' => [
                'performance', 'optimization', 'caching', 'cache', 'lazy loading',
                'conditional testing', 'git based execution',
            ],
            'code-quality' => [
                'code quality', 'formatting', 'pint', 'documentation', 'refactoring',
                'maintenance', 'code consistency',
            ],
            'development-environment' => [
                'node.js', 'vite', 'development environment', 'tooling', 'gitignore',
                'version control', 'nvm', 'crypto.hash',
            ],
            'authentication-authorization' => [
                'authentication', 'authorization', 'rbac', 'role-based', 'auth',
                'password reset', 'login', 'logout',
            ],
        ];

        foreach ($keywords as $subcategory => $keywordList) {
            foreach ($keywordList as $keyword) {
                if (str_contains($contentLower, $keyword)) {
                    return $subcategory;
                }
            }
        }

        // Check tags
        foreach ($tagsLower as $tag) {
            if (in_array($tag, ['phpunit', 'pest', 'test', 'testing'])) {
                return 'testing-patterns';
            }
            if (in_array($tag, ['vue', 'frontend', 'component'])) {
                return 'frontend-development';
            }
            if (in_array($tag, ['database', 'migration', 'eloquent'])) {
                return 'database-backend';
            }
            if (in_array($tag, ['inertia', 'routing'])) {
                return 'inertia-routing';
            }
        }

        // Default fallback
        return null;
    }
};
