<?php
namespace Drupal\iish_conference_reviews\Controller;

use Drupal\Core\Controller\ControllerBase;

use Drupal\Core\Link;
use Drupal\Core\Url;

use Drupal\iish_conference\API\ApiCriteriaBuilder;
use Drupal\iish_conference\API\Domain\PaperReviewApi;
use Drupal\iish_conference\API\LoggedInUserDetails;
use Drupal\iish_conference\API\CachedConferenceApi;

use Drupal\iish_conference\ConferenceTrait;

/**
 * The controller for the reviews.
 */
class ReviewsController extends ControllerBase {
  use ConferenceTrait;

  /**
   * List all reviews.
   * @return array Render array.
   */
  public function listReviews() {
    if ($this->redirectIfNotLoggedIn()) return array();

    $props = new ApiCriteriaBuilder();
    $reviewsLeft = PaperReviewApi::getListWithCriteria(
      $props
        ->eq('reviewer_id', LoggedInUserDetails::getId())
        ->eq('review', NULL)
        ->get()
    )->getResults();

    $reviewsLeftList = array();
    foreach ($reviewsLeft as $review) {
      $reviewsLeftList[] = array(
        array(
          '#markup' => Link::fromTextAndUrl($review->getPaper()->__toString(),
            Url::fromRoute(
              'iish_conference_reviews.form',
              array('paper' => $review->getPaperId())
            ))->toString()
        ),
      );
    }

    $props = new ApiCriteriaBuilder();
    $reviewed = PaperReviewApi::getListWithCriteria(
      $props
        ->eq('reviewer_id', LoggedInUserDetails::getId())
        ->ne('review', NULL)
        ->get()
    )->getResults();

    $reviewedList = array();
    foreach ($reviewed as $review) {
      $reviewedList[] = $review->getPaper()->__toString();
    }

    return array(
      $this->backToPersonalPageLink(),

      array(
        '#theme' => 'iish_conference_container',
        '#fields' => array(
          array('header' => iish_t('Papers to be reviewed')),
          array(
            '#theme' => 'item_list',
            '#type' => 'ol',
            '#attributes' => array('class' => 'papers_open_review'),
            '#items' => $reviewsLeftList,
          )
        )
      ),

      array(
        '#theme' => 'iish_conference_container',
        '#fields' => array(
          array('header' => iish_t('Papers reviewed')),
          array(
            '#theme' => 'item_list',
            '#type' => 'ol',
            '#attributes' => array('class' => 'papers_reviewed'),
            '#items' => $reviewedList,
          )
        )
      )
    );
  }

  /**
   * The reviewer title.
   * @return string The reviewer title.
   */
  public function getReviewerTitle() {
    try {
      return iish_t('I would like to participate as a reviewer in the')
        . ' ' . CachedConferenceApi::getEventDate()->getLongNameAndYear();
    }
    catch (\Exception $exception) {
      return t('I would like to participate as a reviewer');
    }
  }
}
