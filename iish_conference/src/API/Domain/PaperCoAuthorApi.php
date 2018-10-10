<?php
namespace Drupal\iish_conference\API\Domain;

use Drupal\iish_conference\API\CRUDApiClient;

/**
 * Holds a paper coauthor obtained from the API
 */
class PaperCoAuthorApi extends CRUDApiClient {
  protected $user_id;
  protected $paper_id;
  protected $addedBy_id;
  protected $user;
  protected $paper;
  protected $addedBy;

  private $userInstance;
  private $paperInstance;
  private $addedByInstance;

  /**
   * The paper to which the co-author is added
   *
   * @return PaperApi The paper
   */
  public function getPaper() {
    if (!$this->paperInstance) {
      $this->paperInstance = PaperApi::createNewInstance($this->paper);
    }
    return $this->paperInstance;
  }

  /**
   * Set the paper to which the co-author is added
   *
   * @param int|PaperApi $paper The paper (id)
   */
  public function setPaper($paper) {
    if ($paper instanceof PaperApi) {
      $paper = $paper->getId();
    }
    $this->paper = NULL;
    $this->paperInstance = NULL;
    $this->paper_id = $paper;
    $this->toSave['paper.id'] = $paper;
  }

  /**
   * The id of the user that is the co-author
   *
   * @return int The user id
   */
  public function getUserId() {
    return $this->user_id;
  }

  /**
   * The id of the paper to which the co-author is added
   *
   * @return int The paper id
   */
  public function getPaperId() {
    return $this->paper_id;
  }

  /**
   * The user that is the co-author
   *
   * @return UserApi The user
   */
  public function getUser() {
    if (!$this->userInstance) {
      $this->userInstance = UserApi::createNewInstance($this->user);
    }
    return $this->userInstance;
  }

  /**
   * Set the user that is the co-author
   *
   * @param int|UserApi $user The user (id)
   */
  public function setUser($user) {
    if ($user instanceof UserApi) {
      $user = $user->getId();
    }
    $this->user = NULL;
    $this->userInstance = NULL;
    $this->user_id = $user;
    $this->toSave['user.id'] = $user;
  }

  /**
   * Returns the user that created this paper co-author
   *
   * @return UserApi The user that created this paper co-author
   */
  public function getAddedBy() {
    if (!$this->addedByInstance) {
      $this->addedByInstance = UserApi::createNewInstance($this->addedBy);
    }
    return $this->addedByInstance;
  }

  /**
   * Set the user who added this paper co-author
   *
   * @param int|UserApi $addedBy The user (id)
   */
  public function setAddedBy($addedBy) {
    if ($addedBy instanceof UserApi) {
      $addedBy = $addedBy->getId();
    }
    $this->addedBy = NULL;
    $this->addedBy_id = $addedBy;
    $this->toSave['addedBy.id'] = $addedBy;
  }

  /**
   * The user id of the user who created this paper co-author
   *
   * @return int The user id of the user who created this paper co-author
   */
  public function getAddedById() {
    return $this->addedBy_id;
  }
} 