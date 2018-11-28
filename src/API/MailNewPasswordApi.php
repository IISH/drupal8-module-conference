<?php
namespace Drupal\iish_conference\API;

use Drupal\iish_conference\API\Domain\UserApi;

/**
 * API that will mail a user his new password
 */
class MailNewPasswordApi {
  private $client;
  private static $apiName = 'mailNewPassword';

  public function __construct() {
    $this->client = new ConferenceApiClient();
  }

  /**
   * Mails the given user a new password
   *
   * @param int|UserAPi $user The user (id) to send a password
   *
   * @return bool Whether the action was successful or not
   */
  public function mailNewPassword($user) {
    if ($user instanceof UserApi) {
      $user = $user->getId();
    }

    $response = $this->client->get(self::$apiName, array(
      'userId' => $user,
    ));

    return ($response != NULL) ? $response['success'] : NULL;
  }
} 