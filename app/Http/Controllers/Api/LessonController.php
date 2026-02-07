<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreLessonsRequest;
use App\Http\Requests\Api\StoreProjectDetailsRequest;
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

            $this->logSuccessfulPush($validated['source_project'], $result);

            return $this->buildResponse($result);
        } catch (\Exception $e) {
            $this->logUnexpectedError($request, $e);

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred while processing lessons',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Store project-specific implementation details (no generic validation).
     */
    public function storeProjectDetails(StoreProjectDetailsRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $result = $this->importService->processProjectDetails(
                $validated['lessons'],
                $validated['source_project']
            );

            $this->logSuccessfulPush($validated['source_project'], $result);

            return $this->buildResponse($result);
        } catch (\Exception $e) {
            $this->logUnexpectedError($request, $e);

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred while processing project details',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * List lessons with optional filtering.
     */
    public function index(Request $request): JsonResponse
    {
        $query = $this->buildFilteredQuery($request);

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

    protected function logSuccessfulPush(string $sourceProject, array $result): void
    {
        Log::info('Lessons pushed successfully', [
            'source_project' => $sourceProject,
            'created' => $result['created'],
            'updated' => $result['updated'],
            'skipped' => $result['skipped'],
            'errors' => count($result['errors']),
        ]);
    }

    protected function buildResponse(array $result): JsonResponse
    {
        $hasErrors = ! empty($result['errors']);
        $hasSuccesses = $result['created'] > 0 || $result['updated'] > 0;

        if ($hasErrors && ! $hasSuccesses) {
            return response()->json([
                'success' => false,
                'message' => 'All lessons failed validation or processing',
                'data' => $result,
            ], 422);
        }

        if ($hasErrors && $hasSuccesses) {
            return response()->json([
                'success' => true,
                'message' => 'Lessons processed with some errors',
                'data' => $result,
            ], 207);
        }

        return response()->json([
            'success' => true,
            'message' => 'Lessons processed successfully',
            'data' => $result,
        ], 201);
    }

    protected function logUnexpectedError(Request $request, \Exception $e): void
    {
        Log::error('Failed to process lessons push', [
            'source_project' => $request->input('source_project'),
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
    }

    protected function buildFilteredQuery(Request $request): \Illuminate\Database\Eloquent\Builder
    {
        $query = Lesson::query();

        if ($request->has('source_project')) {
            $query->bySourceProject($request->source_project);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('category')) {
            $query->byCategory($request->category);
        }

        if ($request->has('tags')) {
            $tags = is_array($request->tags) ? $request->tags : [$request->tags];
            $query->byTags($tags);
        }

        if (! $request->has('include_non_generic')) {
            $query->generic();
        }

        return $query;
    }
}
