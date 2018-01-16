<?php
namespace Drupal\iish_conference\API\Domain;

use Drupal\iish_conference\API\CRUDApiClient;

/**
 * Holds a paper type obtained from the API
 */
class PaperTypeApi extends CRUDApiClient {
  protected $type;

  /**
   * Returns the name of this paper type
   *
   * @return string The type name
   */
  public function getType() {
    return $this->type;
  }

  public function __toString() {
    return $this->getType();
  }
} 