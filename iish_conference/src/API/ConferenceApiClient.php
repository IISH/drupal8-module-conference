<?php
namespace Drupal\iish_conference\API;

use Drupal\iish_conference\OAuth2\Client;
use Drupal\iish_conference\OAuth2\GrantType\ClientCredentials;

/**
 * Client that allows communication with the Conference Management System API
 */
class ConferenceApiClient {
  private $oAuthClient;
  private $requestCache;

  private static $yearCode = NULL;

  public function __construct() {
    $config = \Drupal::config('iish_conference.settings');
    $clientId = $config->get('conference_client_id');
    $clientSecret = $config->get('conference_client_secret');

    $this->oAuthClient = new Client($clientId, $clientSecret);
    $this->requestCache = SimpleApiCache::getInstance();

    $this->oAuthClient->setAccessTokenType(Client::ACCESS_TOKEN_BEARER);
  }

  /**
   * Returns the year code to use when calling the Conference Management System API
   *
   * @return string The year code
   */
  public static function getYearCode() {
    if (self::$yearCode === NULL) {
      return \Drupal::config('iish_conference.settings')
        ->get('conference_date_code');
    }

    return self::$yearCode;
  }

  /**
   * Allows to override the configured year code
   *
   * @param string $yearCode Override the configured year code with a new year code
   */
  public static function setYearCode($yearCode) {
    self::$yearCode = $yearCode;
  }

  /**
   * Make a GET call to the Conference Management System API
   *
   * @param string $apiName The name of the API to call
   * @param array $parameters The parameters to send with the call
   *
   * @return mixed The response message if found, else null is returned
   */
  public function get($apiName, array $parameters) {
    return $this->call($apiName, $parameters, Client::HTTP_METHOD_GET);
  }

  /**
   * Make a POST call to the Conference Management System API
   *
   * @param string $apiName The name of the API to call
   * @param array $parameters The parameters to send with the call
   *
   * @return mixed The response message if found, else null is returned
   */
  public function post($apiName, array $parameters) {
    return $this->call($apiName, $parameters, Client::HTTP_METHOD_POST);
  }

  /**
   * Make a PUT call to the Conference Management System API
   *
   * @param string $apiName The name of the API to call
   * @param array $parameters The parameters to send with the call
   *
   * @return mixed The response message if found, else null is returned
   */
  public function put($apiName, array $parameters) {
    // Make sure we send it with content-type 'application/x-www-form-urlencoded'
    $parameters = http_build_query($parameters, NULL, '&');

    return $this->call($apiName, $parameters, Client::HTTP_METHOD_PUT);
  }

  /**
   * Make a DELETE call to the Conference Management System API
   *
   * @param string $apiName The name of the API to call
   * @param array $parameters The parameters to send with the call
   *
   * @return mixed The response message if found, else null is returned
   */
  public function delete($apiName, array $parameters) {
    return $this->call($apiName, $parameters, Client::HTTP_METHOD_DELETE);
  }

  /**
   * Make a call to the Conference Management System API
   *
   * @param string $apiName The name of the API to call
   * @param array|string $parameters The parameters to send with the call
   * @param string $http_method The HTTP method to use
   *
   * @return mixed The response message if found, else null is returned
   *
   * @throws ConferenceApiException In case of connection problems
   */
  private function call($apiName, $parameters, $http_method = Client::HTTP_METHOD_GET) {
    // See if this request was made before
    $result = $this->requestCache->get($apiName, self::getYearCode(), $parameters, $http_method);

    if ($result === NULL) {
      $url = self::getUrl() . $apiName;

      try {
        // Always use the token from cache first
        $this->requestToken(TRUE);

        $response = $this->oAuthClient->fetch($url, $parameters, $http_method);

        // Authorization error, request a new token and try again
        if (in_array($response['code'], array(302, 401, 403))) {
          $this->requestToken();
          $response = $this->oAuthClient->fetch($url, $parameters, $http_method);
        }

        if ($response['code'] === 200) {
          $result = $response['result'];
          $this->requestCache->set($apiName, self::getYearCode(), $parameters, $http_method, $result);
        }
        else {
          throw new \Exception('Failed to communicate with the conference API: returned ' . $response['code']);
        }
      } catch (\Exception $exception) {
        \Drupal::logger('iish_conference')->error($exception->getMessage());
        throw new ConferenceApiException(t('There are currently problems obtaining the necessary data. ' .
          'Please try again later. We are sorry for the inconvenience.'), $exception);
      }
    }

    return $result;
  }

  /**
   * Request a token to access the API.
   * @param bool $checkCache Check if we have a token in cache, if so, use that one. Otherwise request a new one.
   */
  private function requestToken($checkCache = FALSE) {
    $cacheKey = 'conference_access_token_' . $this->oAuthClient->getClientId();

    if ($checkCache && ($cachedToken = \Drupal::cache()->get($cacheKey))) {
      $this->oAuthClient->setAccessToken($cachedToken->data);
    }
    else {
      $response = $this->oAuthClient->getAccessToken(
        self::getTokenUrl(), ClientCredentials::GRANT_TYPE, array(
        'scope' => 'event' // Request requires a scope, but that may be anything
      ));

      if ($response['code'] === 200) {
        $token = $response['result']['access_token'];
        $this->oAuthClient->setAccessToken($token);
        \Drupal::cache()->set($cacheKey, $token, time() + 60 * 60 * 12);
      }
    }
  }

  /**
   * Returns the url (without the api name) for a API call to the Conference Management System API
   *
   * @return string The url for a API call to the Conference Management System API
   */
  private static function getUrl() {
    $config = \Drupal::config('iish_conference.settings');
    return $config->get('conference_base_url') . $config->get('conference_event_code') .
    '/' . self::getYearCode() . '/api/';
  }

  /**
   *  Returns the url (without the api name) for a token request to the Conference Management System API
   *
   * @return string Returns the url for a token request to the Conference Management System API
   */
  private static function getTokenUrl() {
    return \Drupal::config('iish_conference.settings')
      ->get('conference_base_url') . 'oauth/token';
  }
}
