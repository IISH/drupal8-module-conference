<?php
namespace Drupal\iish_conference\API;

use Drupal\iish_conference\API\Domain\UserApi;

/**
 * API that returns user information of a certain user
 */
class UserInfoApi {
  private $client;
  private static $apiName = 'userInfo';

  public function __construct() {
    $this->client = new ConferenceApiClient();
  }

  /**
   * Returns user info
   *
   * @param int|UserApi $userId The user (id) to obtain information about
   *
   * @return array|null User information or null if unsuccessful
   */
  public function userInfo($userId) {
    if ($userId instanceof UserApi) {
      $userId = $userId->getId();
    }

    $response = $this->client->get(self::$apiName, array(
      'userId' => trim($userId),
    ));

    return $response;
  }
} 