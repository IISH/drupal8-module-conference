<?php
namespace Drupal\iish_conference\API;

/**
 * An empty CRUD API Client for non existing properties
 */
class EmptyApi extends CRUDApiClient {
  public function __construct() {
    $this->id = -1;
  }
}