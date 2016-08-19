<?php
namespace Drupal\iish_conference\API\Domain;

use Drupal\iish_conference\API\CRUDApiClient;

/**
 * Holds a paper state obtained from the API
 */
class PaperStateApi extends CRUDApiClient {
  const NO_PAPER = 0;
  const NEW_PAPER = 1;
  const PAPER_ACCEPTED = 2;
  const PAPER_NOT_ACCEPTED = 3;
  const PAPER_IN_CONSIDERATION = 4;

  protected $description;
  protected $shortDescription;

  /**
   * Returns a description of this paper state
   *
   * @return string A description of this paper state
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * Returns a short description of this paper state
   *
   * @return string A short description of this paper state
   */
  public function getShortDescription() {
    return $this->shortDescription;
  }

  public function __toString() {
    return $this->getDescription();
  }
} 