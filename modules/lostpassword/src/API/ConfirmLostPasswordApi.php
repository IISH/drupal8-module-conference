<?php
namespace Drupal\iish_conference_lostpassword\API;

use Drupal\iish_conference\API\ConferenceApiClient;

/**
 * API that allows for checking the request code of a user when he lost his password
 */
class ConfirmLostPasswordApi {
  const NOT_FOUND = 0;
  const ACCEPT = 1;
  const PASSWORD_ALREADY_SENT = 2;
  const CODE_EXPIRED = 3;
  const ERROR = 4;
  
  private $client;
  private static $apiName = 'confirmLostPassword';

  public function __construct() {
    $this->client = new ConferenceApiClient();
  }

  /**
   * Confirm a lost password request with the request code and id of the user
   *
   * @param int $userId The user id of the requesting user
   * @param string $code The request code
   *
   * @return int|null The status code or null if not successful
   */
  public function confirmLostPassword($userId, $code) {
    $response = $this->client->get(self::$apiName, array(
      'userId' => $userId,
      'code' => $code,
    ));

    return ($response != NULL) ? $response['status'] : NULL;
  }
} 