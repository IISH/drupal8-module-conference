<?php
namespace Drupal\iish_conference\API;

/**
 * A wrapper class of results obtained via the CRUDApiClient
 */
class CRUDApiResults {
  private $totalSize;
  private $results;

  public function __construct($totalSize, array $results = array()) {
    $this->totalSize = $totalSize;
    $this->results = $results;
  }

  /**
   * Add the obtained results
   *
   * @param CRUDApiClient[] $results
   */
  public function setResults(array $results) {
    $this->results = $results;
  }

  /**
   * Returns a list with all obtained results
   *
   * @return CRUDApiClient[] $results
   */
  public function getResults() {
    return $this->results;
  }

  /**
   * Sets the total size of all results, is always bigger or equal to the number of results returned
   *
   * @param int $totalSize The total size of all results
   */
  public function setTotalSize($totalSize) {
    $this->totalSize = $totalSize;
  }

  /**
   * Returns the total size of all results, is always bigger or equal to the number of results returned
   *
   * @return int $totalSize The total size of all results
   */
  public function getTotalSize() {
    return $this->totalSize;
  }

  /**
   * Adds to the obtained results
   *
   * @param CRUDApiClient $instance The instance to add to the results
   */
  public function addToResults($instance) {
    $this->results[] = $instance;
  }

  /**
   * Returns the very first results, if there are any results at all
   *
   * @return CRUDApiClient|null The first result
   */
  public function getFirstResult() {
    if (count($this->results) >= 1) {
      return $this->results[0];
    }
    else {
      return NULL;
    }
  }
} 