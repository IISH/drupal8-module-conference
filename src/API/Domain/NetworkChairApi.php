<?php
namespace Drupal\iish_conference\API\Domain;

use Drupal\iish_conference\API\CRUDApiClient;

/**
 * Holds a network chair obtained from the API
 */
class NetworkChairApi extends CRUDApiClient {
  protected $network_id;
  protected $chair_id;
  protected $chair;
  protected $isMainChair;
  protected $votedAdvisoryBoard;

  private $chairInstance;

  /**
   * Returns the user id of this chair
   *
   * @return int the user id of this chair
   */
  public function getChairId() {
    return $this->chair_id;
  }

  /**
   * Returns the user instance who is the chair in the network
   *
   * @return UserApi The user instance
   */
  public function getChair() {
    if (!$this->chairInstance) {
      $this->chairInstance = UserApi::createNewInstance($this->chair);
    }

    return $this->chairInstance;
  }

  /**
   * Returns whether this chair is the main chair
   *
   * @return bool Whether this chair is the main chair
   */
  public function isMainChair() {
    return $this->isMainChair;
  }

  /**
   * Returns the id of the network of this chair
   *
   * @return int The network id
   */
  public function getNetworkId() {
    return $this->network_id;
  }

  /**
   * Returns whether this chair has voted for the advisory board
   *
   * @return bool Whether this chair has voted for the advisory board
   */
  public function hasVotedAdvisoryBoard() {
    return $this->votedAdvisoryBoard;
  }

  /**
   * Set whether this chair has voted for the advisory board
   *
   * @param bool $votedAdvisoryBoard Whether this chair has voted for the advisory board
   */
  public function setVotedAdvisoryBoard($votedAdvisoryBoard) {
    $this->votedAdvisoryBoard = (bool) $votedAdvisoryBoard;
    $this->toSave['votedAdvisoryBoard'] = $this->votedAdvisoryBoard;
  }

  public function __toString() {
    return $this->getChair()->__toString();
  }
} 