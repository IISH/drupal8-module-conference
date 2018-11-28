<?php
namespace Drupal\iish_conference\API\Domain;

use Drupal\iish_conference\API\CRUDApiClient;
use Drupal\iish_conference\API\CRUDApiMisc;

/**
 * Holds a reviewer obtained from the API
 */
class ReviewerApi extends CRUDApiClient {
  protected $user_id;
  protected $confirmed;

  private $user;

  /**
   * Returns the id of the user
   *
   * @return int The user id
   */
  public function getUserId() {
    return $this->user_id;
  }

  /**
   * Returns the user
   *
   * @return UserApi The user
   */
  public function getUser() {
    if (!$this->user) {
      $this->user = CRUDApiMisc::getById(new UserApi(), $this->user_id);
    }

    return $this->user;
  }

  /**
   * Returns whether this user has confirmed the role of reviewer
   *
   * @return bool|null Whether this user has confirmed the role of reviewer
   *                   or NULL if not decided yet
   */
  public function hasConfirmed() {
    return $this->confirmed;
  }

  /**
   * Set whether this user has confirmed the role of reviewer
   *
   * @param bool $confirms Whether this user has confirmed the role of reviewer
   */
  public function setConfirmed($confirms) {
    $this->confirmed = (bool) $confirms;
    $this->toSave['confirmed'] = $this->confirmed;
  }

  public function __toString() {
    return $this->getUser()->__toString();
  }
} 