<?php
namespace Drupal\iish_conference_programme\API;

use Drupal\iish_conference\API\ConferenceApiClient;

/**
 * API for obtaining the current programme
 */
class ProgrammeApi {
  private $client;
  private static $apiName = 'programme';

  public function __construct() {
    $this->client = new ConferenceApiClient();
  }

  /**
   * Retrieves the programme with the provided filters
   *
   * @param int|null $dayId Only return the programme for the day with the specified id
   * @param int|null $timeId Only return the programme for the time slot with the specified id
   * @param int|null $networkId Only return the programme for sessions in the network with the specified id
   * @param int|null $roomId Only return the programme for sessions in the room with the specified id
   * @param int|null $participantId Only return the programme for sessions in the favorites list
   *                                   of the participant with the specified id
   * @param string|null $terms Only return the programme for sessions that contain
   *                                   one or more of the specified terms (Terms are separated by a space)
   * @param int|null $sessionId Only return the programme for a particular session with the specified id
   *
   * @return array|null Returns the programme
   */
  public function getProgramme($dayId = NULL, $timeId = NULL, $networkId = NULL, $roomId = NULL,
                               $participantId = NULL, $terms = NULL, $sessionId = NULL) {
    $params = array();
    if (is_int($dayId)) {
      $params['dayId'] = $dayId;
    }
    if (is_int($timeId)) {
      $params['timeId'] = $timeId;
    }
    if (is_int($networkId)) {
      $params['networkId'] = $networkId;
    }
    if (is_int($roomId)) {
      $params['roomId'] = $roomId;
    }
    if (is_int($participantId)) {
      $params['participantId'] = $participantId;
    }
    if (!is_null($terms) && (strlen(trim($terms)) > 0)) {
      $params['terms'] = trim($terms);
    }
    if (is_int($sessionId)) {
      $params['sessionId'] = $sessionId;
    }

    return $this->client->get(self::$apiName, $params);
  }

  /**
   * Retrieves the programme for the specified day and/or time slot
   *
   * @param int|null $dayId The day id to filter on
   * @param int|null $timeId The time slot id to filter on
   *
   * @return array|null Returns the programme
   */
  public function getProgrammeForDayAndTime($dayId, $timeId = NULL) {
    return $this->getProgramme($dayId, $timeId);
  }

  /**
   * Retrieves the programme for sessions in the network with the specified id
   *
   * @param int|null $networkId The network id to filter on
   *
   * @return array|null Returns the programme
   */
  public function getProgrammeForNetwork($networkId) {
    return $this->getProgramme(NULL, NULL, $networkId);
  }

  /**
   * Retrieves the programme for sessions in the room with the specified id
   *
   * @param int|null $roomId The room id to filter on
   *
   * @return array|null Returns the programme
   */
  public function getProgrammeForRoom($roomId) {
    return $this->getProgramme(NULL, NULL, NULL, $roomId);
  }

  /**
   * Retrieves the programme for sessions in the favorites list of the participant with the specified id
   *
   * @param int|null $participantId The participant id to filter on
   *
   * @return array|null Returns the programme
   */
  public function getProgrammeForParticipant($participantId) {
    return $this->getProgramme(NULL, NULL, NULL, NULL, $participantId);
  }

  /**
   * Retrieves the programme for sessions that contain one or more of the specified terms
   *
   * @param string|null $terms The terms, separated by a space
   *
   * @return array|null Returns the programme
   */
  public function getProgrammeForTerms($terms) {
    return $this->getProgramme(NULL, NULL, NULL, NULL, NULL, $terms);
  }

  /**
   * Retrieves the programme for sessions in the favorites list of the participant with the specified id
   *
   * @param int|null $sessionId The participant id to filter on
   *
   * @return array|null Returns the programme
   */
  public function getProgrammeForSession($sessionId) {
    return $this->getProgramme(NULL, NULL, NULL, NULL, NULL, NULL, $sessionId);
  }
} 
