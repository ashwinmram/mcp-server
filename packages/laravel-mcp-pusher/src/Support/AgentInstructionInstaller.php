<?php

namespace LaravelMcpPusher\Support;

use Illuminate\Support\Facades\File;

class AgentInstructionInstaller
{
    /**
     * @return array{installed: int, skipped: int}
     */
    public function copyFile(string $source, string $destination, bool $force): array
    {
        if (! File::exists($source)) {
            return ['installed' => 0, 'skipped' => 0, 'missing' => true];
        }

        File::ensureDirectoryExists(dirname($destination));

        if (File::exists($destination) && ! $force) {
            return ['installed' => 0, 'skipped' => 1, 'missing' => false];
        }

        File::copy($source, $destination);

        return ['installed' => 1, 'skipped' => 0, 'missing' => false];
    }

    /**
     * @param  array<int, string>  $files  Map of stub filename => destination absolute path
     * @return array{installed: int, skipped: int}
     */
    public function copyFiles(string $stubsPath, array $files, bool $force): array
    {
        $installed = 0;
        $skipped = 0;

        foreach ($files as $filename => $destination) {
            $result = $this->copyFile($stubsPath.DIRECTORY_SEPARATOR.$filename, $destination, $force);

            if ($result['missing'] ?? false) {
                continue;
            }

            $installed += $result['installed'];
            $skipped += $result['skipped'];
        }

        return ['installed' => $installed, 'skipped' => $skipped];
    }

    public function stubsPath(string $subdirectory = ''): string
    {
        $base = dirname(__DIR__, 2).'/stubs';

        if ($subdirectory === '') {
            return $base.DIRECTORY_SEPARATOR;
        }

        return $base.DIRECTORY_SEPARATOR.$subdirectory;
    }
}
