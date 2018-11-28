<?php
namespace Drupal\iish_conference_personalpage\API;

use Drupal\iish_conference\API\ConferenceApiClient;
use Drupal\iish_conference\API\Domain\PaperApi;

/**
 * API which allows uploaded papers to be removed
 */
class RemovePaperApi {
	private static $apiName = 'removePaper';
	private $client;

	public function __construct() {
		$this->client = new ConferenceApiClient();
	}

	/**
	 * Remove the uploaded paper for the given paper instance
	 *
	 * @param PaperApi|int $paperId The paper (id) of which the uploaded paper should be removed
	 *
	 * @return bool True if successfully removed, false if a not
	 */
	public function removePaper($paperId) {
		if ($paperId instanceof PaperApi) {
			$paperId = $paperId->getId();
		}

		$response = $this->client->get(self::$apiName, array(
			'paperId' => $paperId,
		));

		return ($response !== null) ? $response['success'] : false;
	}
} 