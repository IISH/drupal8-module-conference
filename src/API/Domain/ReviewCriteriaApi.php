<?php
namespace Drupal\iish_conference\API\Domain;

use Drupal\iish_conference\API\CRUDApiClient;

/**
 * Holds review criteria obtained from the API
 */
class ReviewCriteriaApi extends CRUDApiClient {
  protected $name;
  protected $sortOrder;

  /**
   * Return the name of this review criteria
   *
   * @return string The name of this review criteria
   */
  public function getName() {
    return $this->name;
  }

  /**
   * Return the sorting order of this review criteria
   *
   * @return int The sorting order of this review criteria
   */
  public function getSortOrder() {
    return $this->sortOrder;
  }

  public function __toString() {
    return $this->getName();
  }
} 