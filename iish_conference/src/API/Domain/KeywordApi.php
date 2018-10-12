<?php
namespace Drupal\iish_conference\API\Domain;

use Drupal\iish_conference\API\CRUDApiClient;

/**
 * Holds a keyword obtained from the API
 */
class KeywordApi extends CRUDApiClient {
  protected $keyword;

  /**
   * Return the keyword
   *
   * @return string The keyword
   */
  public function getKeyword() {
    return $this->keyword;
  }

  public function __toString() {
    return $this->getKeyword();
  }
}
