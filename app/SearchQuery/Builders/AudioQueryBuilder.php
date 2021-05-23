<?php
declare( strict_types = 1 );

namespace App\SearchQuery\Builders;

use App\Exceptions\QueryBuilderError;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class AudioQueryBuilder implements QueryBuilder {
    public function __construct(
        private string $recorder,
        private string $args,
        private string $fileFormat,
        private float $timeoutSecs
    ) {}

    public function get(): string {
        // TODO: Delete these files once every 4 hours or so.
        $recordingPath = $this->getRecordingPath();
        $audioCaptureCommand = $this->runCommand($recordingPath);

        if (file_exists($recordingPath)) {
            throw new QueryBuilderError(
                "No audio file was generated for the search query." .
                "\nCapture command: $audioCaptureCommand"
            );
        }

        return 'Jarvis Iron Man';
    }

    private function getRecordingPath(): string {
        $tmpFolder = storage_path('tmp');
        $tmpFilename = Str::uuid()->toString();
        $fullPath = $tmpFolder . '/' . $tmpFilename . '.' . rtrim($this->fileFormat, '.');
        return $fullPath;
    }

    private function runCommand(string $fullPath): void {
        $audioCaptureCommand =  "{$this->recorder} {$this->args} $fullPath";

        $process = Process::fromShellCommandline($audioCaptureCommand);
        $process->setTimeout($this->timeoutSecs);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new QueryBuilderError(
                "There was an error while capturing the search query via audio.".
                "\nCapture command: $audioCaptureCommand " .
                "\nError output: " . $process->getErrorOutput()
            );
        }
    }
}