<?php
namespace Drupal\iish_conference_network_forchairs\API;

use Drupal\iish_conference\API\ConferenceApiClient;

use Drupal\iish_conference\API\Domain\UserApi;
use Drupal\iish_conference\API\Domain\PaperApi;
use Drupal\iish_conference\API\Domain\NetworkApi;
use Drupal\iish_conference\API\Domain\SessionApi;
use Drupal\iish_conference\API\Domain\ParticipantDateApi;
use Drupal\iish_conference\API\Domain\ParticipantTypeApi;

/**
 * API that returns all participants in a session
 */
class ParticipantsInSessionApi {
  private static $apiName = 'participantsInSession';
  private $client;

  public function __construct() {
    $this->client = new ConferenceApiClient();
  }

  /**
   * Returns all the participants for a session in a network along with the user, paper and type details.
   * If a non existing session id is given, the participants not in a session,
   * but proposing for the given network are returned instead
   *
   * @param int|NetworkApi $networkId The network in question
   * @param int|SessionApi|null $sessionId The session in question
   *
   * @return array|bool The results, an array with the UserApi, PaperApi and ParticipantTypeApi,
   * or false in case of a failure
   */
  public function getParticipantsForSession($networkId, $sessionId) {
    if ($networkId instanceof NetworkApi) {
      $networkId = $networkId->getId();
    }

    if ($sessionId instanceof SessionApi) {
      $sessionId = $sessionId->getId();
    }
    else {
      if ($sessionId === NULL) {
        $sessionId = -1;
      }
    }

    $response = $this->client->get(self::$apiName, array(
      'networkId' => $networkId,
      'sessionId' => $sessionId
    ));

    return ($response !== NULL) ? $this->processResponse($response) : FALSE;
  }

  /**
   * Makes sure to properly return the results
   *
   * @param array $response The response obtained from the API
   *
   * @return array The results, an array with the UserApi, the PaperApi and the ParticipantTypeApi
   */
  private function processResponse($response) {
    $results = array();
    foreach ($response as $participantInfo) {
      $user = UserApi::getUserFromArray($participantInfo[0]);
      $participantDate = ParticipantDateApi::getParticipantDateFromArray($participantInfo[1]);
      $paper = ($participantInfo[2] === NULL) ? NULL : PaperApi::getPaperFromArray($participantInfo[2]);
      $type = (isset($participantInfo[3]) && ($participantInfo[3] !== NULL)) ? ParticipantTypeApi::getParticipantTypeFromArray($participantInfo[3]) : NULL;

      $results[] = array(
        'user' => $user,
        'paper' => $paper,
        'type' => $type,
        'participantDate' => $participantDate
      );
    }

    return $results;
  }
}
