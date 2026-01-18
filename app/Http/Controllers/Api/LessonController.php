<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreLessonsRequest;
use App\Models\Lesson;
use App\Services\LessonImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LessonController extends Controller
{
    public function __construct(
        protected LessonImportService $importService
    ) {}

    /**
     * Store lessons pushed from local projects.
     */
    public function store(StoreLessonsRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $result = $this->importService->processLessons(
                $validated['lessons'],
                $validated['source_project']
            );

            // Log successful push
            Log::info('Lessons pushed successfully', [
                'source_project' => $validated['source_project'],
                'created' => $result['created'],
                'updated' => $result['updated'],
                'skipped' => $result['skipped'],
                'errors' => count($result['errors']),
            ]);

            // Determine appropriate status code and message
            $hasErrors = ! empty($result['errors']);
            $hasSuccesses = $result['created'] > 0 || $result['updated'] > 0;

            if ($hasErrors && ! $hasSuccesses) {
                // All lessons failed
                return response()->json([
                    'success' => false,
                    'message' => 'All lessons failed validation or processing',
                    'data' => $result,
                ], 422);
            }

            if ($hasErrors && $hasSuccesses) {
                // Partial success
                return response()->json([
                    'success' => true,
                    'message' => 'Lessons processed with some errors',
                    'data' => $result,
                ], 207); // 207 Multi-Status
            }

            // All successful
            return response()->json([
                'success' => true,
                'message' => 'Lessons processed successfully',
                'data' => $result,
            ], 201);
        } catch (\Exception $e) {
            // Log unexpected errors
            Log::error('Failed to process lessons push', [
                'source_project' => $request->input('source_project'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred while processing lessons',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * List lessons with optional filtering.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Lesson::query();

        // Filter by source project
        if ($request->has('source_project')) {
            $query->bySourceProject($request->source_project);
        }

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Filter by category
        if ($request->has('category')) {
            $query->byCategory($request->category);
        }

        // Filter by tags
        if ($request->has('tags')) {
            $tags = is_array($request->tags) ? $request->tags : [$request->tags];
            $query->byTags($tags);
        }

        // Only show generic lessons by default
        if (! $request->has('include_non_generic')) {
            $query->generic();
        }

        $lessons = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json($lessons);
    }

    /**
     * Show a single lesson.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $lesson = Lesson::findOrFail($id);

            return response()->json($lesson);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lesson not found',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve lesson', [
                'lesson_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving the lesson',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }
}
