<?php
declare(strict_types=1);

namespace App\IntentActionReceivers;

use App\Exceptions\IntentActionReceiverError;
use App\SpeechToText\SpeechToTextProvider;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class AudioIntentActionReceiver implements IntentActionReceiver
{
	public function __construct(
		private string $recorder,
		private string $args,
		private string $fileFormat,
		private float $timeoutSecs,
		private SpeechToTextProvider $speechToTextProvider
	) {
	}

	public function get(): string
	{
		// TODO: Delete these files once every 4 hours or so.
		$recordingPath = $this->getRecordingPath();
		$audioCaptureCommand = $this->runCommand($recordingPath);

		if (!file_exists($recordingPath)) {
			throw new IntentActionReceiverError(
				"No audio file was generated for the search query." .
				"\nCapture command: {$audioCaptureCommand->getCommandLine()}"
			);
		}

		// TODO: Undo this hardcoded value
		$speechToText = $this->speechToTextProvider->transcribe($recordingPath, 'cd', 'en-IN');
		return $speechToText->getTranscript();
	}

	private function getRecordingPath(): string
	{
		$tmpFolder = storage_path('tmp');
		$tmpFilename = Str::uuid()->toString();
		return $tmpFolder . '/' . $tmpFilename . '.' . rtrim($this->fileFormat, '.');
	}

	private function runCommand(string $fullPath): Process
	{
		$audioCaptureCommand = "{$this->recorder} {$this->args} -d {$this->timeoutSecs} -N $fullPath";

		$process = Process::fromShellCommandline($audioCaptureCommand);
		$process->setTimeout($this->timeoutSecs + 2);
		$process->run();

		if (!$process->isSuccessful()) {
			throw new IntentActionReceiverError(
				"There was an error while capturing the search query via audio." .
				"\nCapture command: $audioCaptureCommand " .
				"\nError output: " . $process->getErrorOutput()
			);
		}

		return $process;
	}
}
