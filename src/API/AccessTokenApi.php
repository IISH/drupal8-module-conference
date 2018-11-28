<?php
namespace Drupal\iish_conference\API;

use Drupal\iish_conference\API\Domain\UserApi;

/**
 * API that returns a token for a specific user, to access resources in the CMS
 */
class AccessTokenApi {
  private $client;
  private static $apiName = 'accessToken';

  public function __construct() {
    $this->client = new ConferenceApiClient();
  }

  /**
   * Returns an access token for the CMS for the given user
   *
   * @param int|UserAPI $userId The user (id)
   *
   * @return string|null The access token for this user or null if not successful
   */
  public function accessToken($userId) {
    if ($userId instanceof UserApi) {
      $userId = $userId->getId();
    }

    $response = $this->client->get(self::$apiName, array(
      'userId' => $userId
    ));

    return (($response !== NULL) && $response['success']) ? $response['access_token'] : NULL;
  }
} 