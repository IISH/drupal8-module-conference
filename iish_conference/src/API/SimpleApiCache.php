<?php
namespace Drupal\iish_conference\API;

/**
 * A very simple API caching system, simply caching responses in memory for the current request only
 */
class SimpleApiCache {
  private $requestCache = array();
  private static $instance;

  // Singleton, so no constructor or cloning allowed
  private function __construct() {
  }

  private function __clone() {
  }

  /**
   * Returns an instance (singleton) of this class
   *
   * @return SimpleApiCache The singleton instance
   */
  public static function getInstance() {
    if (!self::$instance) {
      self::$instance = new SimpleApiCache();
    }

    return self::$instance;
  }

  /**
   * Sets a item in the cache
   *
   * @param string $apiName The name of the API called
   * @param string $yearCode The year code used in the API call
   * @param array $parameters The parameters send with the API call
   * @param string $http_method The HTTP method used
   * @param array|null $response The response
   */
  public function set($apiName, $yearCode, $parameters, $http_method, $response) {
    $cacheKey = $apiName . ':' . $yearCode . ':' . $http_method . ':' . serialize($parameters);
    $this->requestCache[$cacheKey] = $response;
  }

  /**
   * Returns an item from the cache, if it exists
   *
   * @param string $apiName The name of the API called
   * @param string $yearCode The year code used in the API call
   * @param array $parameters The parameters send with the API call
   * @param string $http_method The HTTP method used
   *
   * @return array|null The response found in the cache
   */
  public function get($apiName, $yearCode, $parameters, $http_method) {
    $cacheKey = $apiName . ':' . $yearCode . ':' . $http_method . ':' . serialize($parameters);
    if (array_key_exists($cacheKey, $this->requestCache)) {
      return $this->requestCache[$cacheKey];
    }
    else {
      return NULL;
    }
  }
} 