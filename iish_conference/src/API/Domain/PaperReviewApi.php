<?php
namespace Drupal\iish_conference\API\Domain;

use Drupal\iish_conference\API\CRUDApiClient;
use Drupal\iish_conference\API\CRUDApiMisc;

/**
 * Holds a paper review obtained from the API
 */
class PaperReviewApi extends CRUDApiClient {
  protected $paper_id;
  protected $reviewer_id;
  protected $review;
  protected $comments;
  protected $avgScore;

  private $paper;
  private $reviewer;
  private $scores;

  /**
   * Returns the id of the paper to be reviewed
   *
   * @return int The paper id
   */
  public function getPaperId() {
    return $this->paper_id;
  }

  /**
   * Returns the paper to be reviewed
   *
   * @return PaperApi The paper
   */
  public function getPaper() {
    if (!$this->paper) {
      $this->paper = CRUDApiMisc::getById(new PaperApi(), $this->paper_id);
    }

    return $this->paper;
  }

  /**
   * Returns the id of the user reviewing the paper
   *
   * @return int The user id
   */
  public function getReviewerId() {
    return $this->reviewer_id;
  }

  /**
   * Returns the user reviewing the paper
   *
   * @return UserApi The user
   */
  public function getReviewer() {
    if (!$this->reviewer) {
      $this->reviewer = CRUDApiMisc::getById(new UserApi(), $this->reviewer_id);
    }

    return $this->reviewer;
  }

  /**
   * Returns the review
   *
   * @return string|null The review
   */
  public function getReview() {
    return $this->review;
  }

  /**
   * Set the review
   *
   * @param string|null $review The review
   */
  public function setReview($review) {
    $review = (($review !== NULL) && strlen(trim($review)) > 0) ? trim($review) : NULL;

    $this->review = $review;
    $this->toSave['review'] = $review;
  }

  /**
   * Returns the comments of this review
   *
   * @return string|null The comments of this review
   */
  public function getComments() {
    return $this->comments;
  }

  /**
   * Returns the average score of this review
   *
   * @return double|null The average score of this review
   */
  public function getAvgScore() {
    return $this->avgScore;
  }

  /**
   * Set the average score of this review
   *
   * @param int|null $avgScore The average score of this review
   */
  public function setAvgScore($avgScore) {
    $this->avgScore = $avgScore;
    $this->toSave['avgScore'] = $avgScore;
  }

  /**
   * Returns the scores gives for this review
   *
   * @return PaperReviewScoreApi[] The scores gives for this review
   */
  public function getScores() {
    if (!$this->scores) {
      $this->scores =
        CRUDApiMisc::getAllWherePropertyEquals(new PaperReviewScoreApi(), 'paperReview_id', $this->getId())
          ->getResults();
    }

    return $this->scores;
  }

  public function __toString() {
    return $this->getPaper()->__toString();
  }
} 