<?php
namespace Drupal\iish_conference_network_sessionpapersaccepted_xls\API;

use Drupal\iish_conference\API\Domain\NetworkApi;
use Drupal\iish_conference\API\ConferenceApiClient;

/**
 * API that returns all participants in a network
 */
class ParticipantsInNetworkSessionsPaperAcceptedApi {
	private static $apiName = 'sessionPapersInNetworkAcceptedXls';
	private $client;

	public function __construct() {
		$this->client = new ConferenceApiClient();
	}

	/**
	 * Returns all the accepted participants (last name, first name and email)
	 * that were in accepted sessions of the given network
	 *
	 * @param int|NetworkApi $networkId The network (id) in question
	 * @param bool           $excel     Whether to return an excel (xls) file
	 *
	 * @return mixed The result, or false in case of a failure
	 */
	public function getParticipantsForNetwork($networkId, $excel = false) {
		if ($networkId instanceof NetworkApi) {
			$networkId = $networkId->getId();
		}

		$response = $this->client->get(self::$apiName, array(
			'networkId'     => $networkId,
			'excel'         => $excel,
			'networkName'   => iish_t('Network')->render(),
			'lastName'      => iish_t('Last name')->render(),
			'firstName'     => iish_t('First name')->render(),
			'email'         => iish_t('E-mail')->render(),
      'organisation'  => iish_t('Organisation')->render(),
			'session'       => iish_t('Session')->render(),
			'sessionState'  => iish_t('Session state')->render(),
			'roles'         => iish_t('Roles')->render(),
			'paperTitle'    => iish_t('Paper title')->render(),
			'paperAbstract' => iish_t('Abstract')->render(),
      'paperST'       => iish_t('Paper state')->render(),
		));

		return (($response !== null) && $response['success']) ? $this->processResponse($response, $excel) : false;
	}

	/**
	 * Makes sure to properly return the results
	 *
	 * @param array $response The response obtained from the API
	 * @param bool  $excel    Whether we requested an excel file or not
	 *
	 * @return mixed The result
	 */
	private function processResponse($response, $excel) {
		if ($excel) {
			return base64_decode($response['xls']);
		}
		else {
			return $response['users'];
		}
	}
}
