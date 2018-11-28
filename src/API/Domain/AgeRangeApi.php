<?php
namespace Drupal\iish_conference\API\Domain;

use Drupal\iish_conference\API\CRUDApiClient;

/**
 * Holds an age range obtained from the API
 */
class AgeRangeApi extends CRUDApiClient {
  protected $minAge;
  protected $maxAge;

  /**
   * Returns the minimum age.
   *
   * @return string The minimum age.
   */
  public function getMinAge() {
    return $this->minAge;
  }

  /**
   * Returns the maximum age.
   *
   * @return string The maximum age.
   */
  public function getMaxAge() {
    return $this->maxAge;
  }

  public function __toString() {
    if ($this->minAge && $this->maxAge) {
      return $this->minAge . ' - ' . $this->maxAge;
    }

    if ($this->minAge) {
      return $this->minAge . '+';
    }

    return '< ' . $this->maxAge;
  }
} 