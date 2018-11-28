<?php
namespace Drupal\iish_conference\API\Domain;

use Drupal\iish_conference\API\CRUDApiClient;

/**
 * Holds a volunteering type obtained from the API
 */
class VolunteeringApi extends CRUDApiClient {
  const CHAIR = 1;
  const DISCUSSANT = 2;
  const COACH = 3;
  const PUPIL = 4;

  protected $description;

  /**
   * The description of the volunteering type
   *
   * @return string The description
   */
  public function getDescription() {
    return $this->description;
  }

  public function __toString() {
    return $this->getDescription();
  }
} 