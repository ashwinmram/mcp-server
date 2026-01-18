<?php

namespace App\Mcp\Tools;

use App\Models\Lesson;
use App\Models\LessonUsage;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class MarkLessonHelpful extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Mark a lesson as helpful or not helpful. This provides explicit feedback that improves relevance scoring.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $lessonId = $request->get('lesson_id');
        $wasHelpful = (bool) $request->get('was_helpful', true);

        if (empty($lessonId)) {
            return Response::error('Lesson ID is required');
        }

        // Check if lesson_usages table exists (Phase 3 feature)
        if (! Schema::hasTable('lesson_usages')) {
            return Response::error('Usage tracking is not available. Please run migrations first.');
        }

        $lesson = Lesson::find($lessonId);

        if (! $lesson) {
            return Response::error("Lesson with ID {$lessonId} not found");
        }

        try {
            // Find the most recent usage for this lesson and update it
            // Or create a new usage entry if none exists
            $usage = LessonUsage::where('lesson_id', $lessonId)
                ->orderBy('created_at', 'desc')
                ->first();

            if ($usage) {
                // Update existing usage
                $usage->update(['was_helpful' => $wasHelpful]);
            } else {
                // Create new usage entry with feedback
                LessonUsage::create([
                    'lesson_id' => $lessonId,
                    'query_context' => 'Explicit feedback',
                    'was_helpful' => $wasHelpful,
                    'session_id' => $this->getSessionId($request),
                ]);
            }

            return Response::json([
                'success' => true,
                'message' => $wasHelpful ? 'Lesson marked as helpful' : 'Lesson marked as not helpful',
                'lesson_id' => $lessonId,
            ]);
        } catch (\Exception $e) {
            return Response::error('Failed to mark lesson: '.$e->getMessage());
        }
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, \Illuminate\Contracts\JsonSchema\JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'lesson_id' => $schema->string()->required()->description('The ID of the lesson to mark'),
            'was_helpful' => $schema->boolean()->default(true)->description('Whether the lesson was helpful (default: true)'),
        ];
    }

    /**
     * Get session ID from request metadata or generate one.
     */
    protected function getSessionId(Request $request): ?string
    {
        // Try to get session ID from request metadata (if MCP provides it)
        $metadata = $request->get('_metadata') ?? [];

        if (isset($metadata['session_id'])) {
            return $metadata['session_id'];
        }

        // Generate a session ID based on request fingerprint
        return Str::uuid()->toString();
    }
}
