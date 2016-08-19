<?php
namespace Drupal\iish_conference_lostpassword\API;

use Drupal\iish_conference\API\ConferenceApiClient;

/**
 * API that allows a user to request a new password if she/he lost his/her password
 */
class LostPasswordApi {
  const USER_STATUS_DOES_NOT_EXISTS = 0;
  const USER_STATUS_EXISTS = 1;
  const USER_STATUS_DISABLED = 2;
  const USER_STATUS_DELETED = 3;

  private $client;
  private static $apiName = 'lostPassword';

  public function __construct() {
    $this->client = new ConferenceApiClient();
  }

  /**
   * Indicates that the user has lost his password
   *
   * @param string $email The email of the user
   *
   * @return int|null The status or null if not successful
   */
  public function lostPassword($email) {
    $response = $this->client->get(self::$apiName, array(
      'email' => $email,
    ));

    return (($response != NULL) && $response['success']) ? $response['status'] : NULL;
  }
} 