<?php
namespace Drupal\iish_conference\API\Domain;

use Drupal\iish_conference\API\CachedConferenceApi;
use Drupal\iish_conference\API\CRUDApiClient;
use Drupal\iish_conference\API\CRUDApiMisc;

/**
 * Holds a paper review score obtained from the API
 */
class PaperReviewScoreApi extends CRUDApiClient {
  protected $paperReview_id;
  protected $criteria_id;
  protected $score;

  private $paperReview;
  private $criteria;

  /**
   * Returns the id of the paper review
   *
   * @return int The paper review id
   */
  public function getPaperReviewId() {
    return $this->paperReview_id;
  }

  /**
   * Returns the paper review
   *
   * @return PaperReviewApi The paper review
   */
  public function getPaperReview() {
    if (!$this->paperReview) {
      $this->paperReview = CRUDApiMisc::getById(new PaperReviewApi(), $this->paperReview_id);
    }

    return $this->paperReview;
  }

  /**
   * Set the paper review
   *
   * @param int|PaperReviewApi $paperReview The paper review (id)
   */
  public function setPaperReview($paperReview) {
    if ($paperReview instanceof PaperReviewApi) {
      $paperReview = $paperReview->getId();
    }

    $this->paperReview = NULL;
    $this->paperReview_id = $paperReview;
    $this->toSave['paperReview.id'] = $paperReview;
  }

  /**
   * Returns the id of the review criteria to score
   *
   * @return int The review criteria id
   */
  public function getCriteriaId() {
    return $this->criteria_id;
  }

  /**
   * Returns the review criteria to score
   *
   * @return ReviewCriteriaApi The review criteria to score
   */
  public function getCriteria() {
    if (!$this->criteria) {
      $reviewCriteria = CachedConferenceApi::getReviewCriteria();

      foreach ($reviewCriteria as $criterion) {
        if ($criterion->getId() == $this->criteria_id) {
          $this->criteria = $criterion;
          break;
        }
      }
    }

    return $this->criteria;
  }

  /**
   * Set the review criteria to score
   *
   * @param int|ReviewCriteriaApi $criteria The review criteria (id)
   */
  public function setCriteria($criteria) {
    if ($criteria instanceof ReviewCriteriaApi) {
      $criteria = $criteria->getId();
    }

    $this->criteria = NULL;
    $this->criteria_id = $criteria;
    $this->toSave['criteria.id'] = $criteria;
  }

  /**
   * Returns the score for this criteria
   *
   * @return double The score
   */
  public function getScore() {
    return $this->score;
  }

  /**
   * Sets the score for this criteria
   *
   * @param double|null $score The score for this criteria
   */
  public function setScore($score) {
    $this->score = $score;
    $this->toSave['score'] = $score;
  }

  public function __toString() {
    return $this->getCriteria()->__toString() . ': ' . $this->getScore();
  }
} 