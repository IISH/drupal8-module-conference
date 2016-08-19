<?php
namespace Drupal\iish_conference\API\Domain;

use Drupal\iish_conference\API\CRUDApiClient;

/**
 * Holds an event obtained from the API
 */
class EventApi extends CRUDApiClient {
  protected $code;
  protected $shortName;
  protected $longName;

  /**
   * Returns the code of the event
   *
   * @return string The code of the event
   */
  public function getCode() {
    return $this->code;
  }

  /**
   * Returns the long name of the event
   *
   * @return string The long name of the event
   */
  public function getLongName() {
    return $this->longName;
  }

  /**
   * Returns the short name of the event
   *
   * @return string The short name of the event
   */
  public function getShortName() {
    return $this->shortName;
  }

  public function __toString() {
    return $this->getLongName();
  }
} 