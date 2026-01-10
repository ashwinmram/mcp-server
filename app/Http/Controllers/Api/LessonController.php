<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreLessonsRequest;
use App\Models\Lesson;
use App\Services\LessonImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LessonController extends Controller
{
    public function __construct(
        protected LessonImportService $importService
    ) {
    }

    /**
     * Store lessons pushed from local projects.
     */
    public function store(StoreLessonsRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $result = $this->importService->processLessons(
            $validated['lessons'],
            $validated['source_project']
        );

        return response()->json([
            'success' => true,
            'message' => 'Lessons processed successfully',
            'data' => $result,
        ], 201);
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
        $lesson = Lesson::findOrFail($id);

        return response()->json($lesson);
    }
}

