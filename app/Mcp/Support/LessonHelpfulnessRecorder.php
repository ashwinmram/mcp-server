<?php

namespace App\Mcp\Support;

use App\Models\LessonUsage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;

class LessonHelpfulnessRecorder
{
    /**
     * @return array{success: bool, message: string, lesson_id: string}|null Null when usage tracking unavailable.
     */
    public static function record(string $lessonId, bool $wasHelpful, Request $request): ?array
    {
        if (! Schema::hasTable('lesson_usages')) {
            return null;
        }

        $usage = LessonUsage::where('lesson_id', $lessonId)
            ->orderBy('created_at', 'desc')
            ->first();

        if ($usage) {
            $usage->update(['was_helpful' => $wasHelpful]);
        } else {
            LessonUsage::create([
                'lesson_id' => $lessonId,
                'query_context' => 'Explicit feedback',
                'was_helpful' => $wasHelpful,
                'session_id' => self::getSessionId($request),
            ]);
        }

        return [
            'success' => true,
            'message' => $wasHelpful ? 'Lesson marked as helpful' : 'Lesson marked as not helpful',
            'lesson_id' => $lessonId,
        ];
    }

    public static function getSessionId(Request $request): ?string
    {
        $metadata = $request->get('_metadata') ?? [];

        if (isset($metadata['session_id'])) {
            return $metadata['session_id'];
        }

        return Str::uuid()->toString();
    }
}
