<?php
namespace Drupal\iish_conference_network_proposedparticipants\API;

use Drupal\iish_conference\API\Domain\UserApi;
use Drupal\iish_conference\API\Domain\PaperApi;
use Drupal\iish_conference\API\Domain\SessionApi;
use Drupal\iish_conference\API\Domain\NetworkApi;

use Drupal\iish_conference\API\ConferenceApiClient;

/**
 * API that returns all individual paper proposals of participants for a single network
 */
class ParticipantsInProposedNetworkApi {
  private static $apiName = 'participantsInProposedNetwork';
  private $client;

  public function __construct() {
    $this->client = new ConferenceApiClient();
  }

  /**
   * Returns all the participants with papers proposed for a network
   *
   * @param int|NetworkApi $networkId The network in question
   *
   * @return array|bool The results, an array with the UserApi, PaperApi and SessionApi or false in case of a failure
   */
  public function getParticipantsInProposedNetwork($networkId) {
    if ($networkId instanceof NetworkApi) {
      $networkId = $networkId->getId();
    }

    $response = $this->client->get(self::$apiName, array(
      'networkId' => $networkId
    ));

    return ($response !== NULL) ? $this->processResponse($response) : FALSE;
  }

  /**
   * Makes sure to properly return the results
   *
   * @param array $response The response obtained from the API
   *
   * @return array The results, an array with the UserApi, the PaperApi and the SessionApi
   */
  private function processResponse($response) {
    $results = array();
    foreach ($response as $participantInfo) {
      $user = UserApi::getUserFromArray($participantInfo[0]);
      $paper = ($participantInfo[1] === NULL) ? NULL : PaperApi::getPaperFromArray($participantInfo[1]);
      $session = (isset($participantInfo[2]) && ($participantInfo[2] !== NULL)) ? SessionApi::getSessionFromArray($participantInfo[2]) : NULL;

      $results[] = array(
        'user' => $user,
        'paper' => $paper,
        'session' => $session
      );
    }

    return $results;
  }
}
