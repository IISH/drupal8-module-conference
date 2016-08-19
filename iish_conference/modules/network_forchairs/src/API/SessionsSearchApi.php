<?php
namespace Drupal\iish_conference_network_forchairs\API;

use Drupal\iish_conference\API\Domain\SessionApi;
use Drupal\iish_conference\API\ConferenceApiClient;

/**
 * API that returns all sessions based on search terms
 */
class SessionsSearchApi {
  private static $apiName = 'sessionsSearch';
  private $client;

  public function __construct() {
    $this->client = new ConferenceApiClient();
  }

  /**
   * Allows to search for sessions based on a search term or multiple terms
   *
   * @param string $search The search terms, separated by a space
   *
   * @return SessionApi[]|bool A list with matching sessions or false in case of a failure
   */
  public function getSessions($search) {
    $response = $this->client->get(self::$apiName, array(
      'search' => $search
    ));

    return ($response !== NULL) ? $this->processResponse($response) : FALSE;
  }

  /**
   * Translates the JSON with session data to session objects
   *
   * @param array $response The list with session data
   *
   * @return SessionApi[] The session objects
   */
  private function processResponse(array $response) {
    $sessions = array();
    foreach ($response as $session) {
      $sessions[] = SessionApi::getSessionFromArray($session);
    }

    return $sessions;
  }
}
