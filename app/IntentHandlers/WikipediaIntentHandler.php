<?php
declare(strict_types=1);

namespace App\IntentHandlers;

use App\Exceptions\IntentActionReceiverError;
use App\Exceptions\QueryResultError;
use App\Exceptions\QueryResultNotFound;
use App\IntentActionReceivers\IntentActionReceiver;
use App\Models\Intent;
use App\SearchQuery\Handlers\WikipediaQueryHandler;
use Exception;

class WikipediaIntentHandler implements IntentHandler
{
	private const NAME = 'Wikipedia';

	public function __construct(
		private IntentActionReceiver $intentActionReceiver,
		private WikipediaQueryHandler $queryHandler
	) {
	}

	public function is(Intent $intent): bool
	{
		return $intent->getName() === self::NAME;
	}

	public function getName(): string
	{
		return self::NAME;
	}

	public function handle(Intent $intent): string
	{
		try {
			$searchText = $this->intentActionReceiver->get();
			if (!$searchText) {
				return __('empty_search_query');
			}
			$queryResult = $this->queryHandler->getQueryResult($searchText);
			return $queryResult->getResult();
		} catch (IntentActionReceiverError $e) {
			report($e);
			return __('rhasspy_audio_query_builder_error');
		} catch (QueryResultNotFound) {
			return __('rhasspy_wiki_query_not_found', ['query' => $searchText]);
		} catch (QueryResultError $e) {
			report($e);
			return __('rhasspy_wiki_query_error', ['query' => $searchText]);
		} catch (Exception $e) {
			report($e);
			return __('rhasspy_unknown_processing_error');
		}
	}
}
