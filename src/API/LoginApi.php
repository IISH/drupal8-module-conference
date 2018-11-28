<?php
namespace Drupal\iish_conference\API;

/**
 * API that allows a user to login
 */
class LoginApi {
  private $client;
  private static $apiName = 'login';

  public function __construct() {
    $this->client = new ConferenceApiClient();
  }

  /**
   * Allows a user to login with this email and password
   *
   * @param string $email The email address of the user
   * @param string $password The password of the user
   *
   * @return int The status
   */
  public function login($email, $password) {
    $response = $this->client->get(self::$apiName, array(
      'email' => trim($email),
      'password' => trim($password),
    ));

    return LoggedInUserDetails::setCurrentlyLoggedInWithResponse($response);
  }
} 