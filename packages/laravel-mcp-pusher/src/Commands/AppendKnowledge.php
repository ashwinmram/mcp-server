<?php

namespace LaravelMcpPusher\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;
use LaravelMcpPusher\Services\JsonlDraftService;
use LaravelMcpPusher\Services\KnowledgeEntryValidator;
use LaravelMcpPusher\Support\KnowledgeScope;

class AppendKnowledge extends Command
{
    protected $signature = 'mcp:append
                            {payload? : JSON object for the knowledge entry}
                            {--file= : Path to a JSON file containing one entry object}';

    protected $description = 'Append one knowledge entry to the session draft file (generic or project, routed from payload)';

    public function handle(JsonlDraftService $draftService, KnowledgeEntryValidator $validator): int
    {
        try {
            $payload = $this->resolvePayload();
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return Command::FAILURE;
        }

        $scope = KnowledgeScope::fromPayload($payload);

        try {
            $validator->validateForAppend($payload, $scope);
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return Command::FAILURE;
        }

        if (! isset($payload['metadata']) || ! is_array($payload['metadata'])) {
            $payload['metadata'] = [];
        }

        $payload['metadata']['captured_at'] = $payload['metadata']['captured_at'] ?? now()->toIso8601String();
        $payload['metadata']['source'] = $payload['metadata']['source'] ?? 'agent';

        $draftService->append($payload, $scope);

        $this->info("Appended {$scope->value} knowledge entry to {$scope->draftPath()}");

        return Command::SUCCESS;
    }

    /**
     * @return array<string, mixed>
     */
    protected function resolvePayload(): array
    {
        $file = $this->option('file');

        if ($file !== null) {
            if (! File::exists($file)) {
                throw new InvalidArgumentException("File not found: {$file}");
            }

            $decoded = json_decode(File::get($file), true);

            if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
                throw new InvalidArgumentException('File must contain a single JSON object.');
            }

            return $decoded;
        }

        $payload = $this->argument('payload');

        if ($payload === null || trim($payload) === '') {
            throw new InvalidArgumentException('Provide a JSON payload argument or use --file=.');
        }

        $decoded = json_decode($payload, true);

        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
            throw new InvalidArgumentException('Payload must be valid JSON object: '.json_last_error_msg());
        }

        return $decoded;
    }
}
