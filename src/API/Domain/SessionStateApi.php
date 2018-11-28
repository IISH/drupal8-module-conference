<?php
namespace Drupal\iish_conference\API\Domain;

use Drupal\iish_conference\API\CRUDApiClient;

/**
 * Holds a session state obtained from the API
 */
class SessionStateApi extends CRUDApiClient {
  const NEW_SESSION = 1;
  const SESSION_ACCEPTED = 2;
  const SESSION_NOT_ACCEPTED = 3;
  const SESSION_IN_CONSIDERATION = 4;

  protected $description;
  protected $shortDescription;

  /**
   * Returns the description of this session state
   *
   * @return string The description
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * Returns the short description of this session state
   *
   * @return string The short description
   */
  public function getShortDescription() {
    return $this->shortDescription;
  }

  /**
   * Returns a simple version of the description, so 'Session Accepted' becomes 'Accepted'
   *
   * @return string The simple description
   */
  public function getSimpleDescription() {
    return trim(str_replace('Session', '', $this->getDescription()));
  }

  public function __toString() {
    return $this->getDescription();
  }
} 