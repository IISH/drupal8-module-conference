<?php
namespace Drupal\iish_conference_reviews\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use Drupal\iish_conference\API\AccessTokenApi;
use Drupal\iish_conference\API\CachedConferenceApi;
use Drupal\iish_conference\API\SettingsApi;
use Drupal\iish_conference\API\Domain\PaperApi;
use Drupal\iish_conference\API\Domain\PaperReviewApi;
use Drupal\iish_conference\API\Domain\PaperReviewScoreApi;
use Drupal\iish_conference\API\ApiCriteriaBuilder;
use Drupal\iish_conference\API\LoggedInUserDetails;

use Drupal\iish_conference\ConferenceMisc;
use Drupal\iish_conference\ConferenceTrait;
use Drupal\iish_conference\Markup\ConferenceHTML;

/**
 * The paper review form.
 */
class ReviewForm extends FormBase {
  use ConferenceTrait;

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'conference_review';
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param PaperApi $paper The paper to review.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $paper = NULL) {
    // redirect to login page
    if ($this->redirectIfNotLoggedIn()) return array();

    $props = new ApiCriteriaBuilder();
    $paperReview = PaperReviewApi::getListWithCriteria(
      $props
        ->eq('paper_id', $paper->getId())
        ->eq('reviewer_id', LoggedInUserDetails::getUser()->getId())
        ->get()
    )->getFirstResult();
    $form_state->set('paper_review', $paperReview);

    if ($paperReview === NULL) {
      drupal_set_message(iish_t('This is not a valid paper or you were not assigned to review this paper!'), 'error');
      return array();
    }

    if ($paperReview->getReview() !== NULL) {
      drupal_set_message(iish_t('You have already reviewed the paper!'), 'error');
      return array();
    }

    $accessTokenApi = new AccessTokenApi();
    $token = $accessTokenApi->accessToken(LoggedInUserDetails::getId());

    $fields = array();
    $fields[] = array(
        'label' => 'Paper',
        'value' => $paper->getTitle(),
    );
    $fields[] = array(
        'label' => 'Paper abstract',
        'value' => ConferenceMisc::getHTMLForLongText($paper->getAbstr()),
        'html' => TRUE,
        'newLine' => TRUE,
    );

    if (SettingsApi::getSetting(SettingsApi::SHOW_PAPER_TYPES, 'bool')) {
      $fields[] = array(
          'label' => 'Paper type',
          'value' => $paper->getType()
      );
    }

    $fields[] = new ConferenceHTML(
        '<a href="' . $paper->getDownloadURL($token) . '">' .
        '<span class="download-icon"></span> ' .
        iish_t('Download paper') .
        '</a>',
        TRUE
    );

    $form['paper'] = array(
      '#theme' => 'iish_conference_container',
      '#styled' => FALSE,
      '#fields' => $fields,
    );

    $form['review'] = array(
      '#type' => 'textarea',
      '#title' => iish_t('Review'),
      '#required' => TRUE,
      '#rows' => 10,
    );

    $form['comments'] = array(
      '#type' => 'textarea',
      '#title' => iish_t('Confidential remarks'),
      '#description' => '<em>' . iish_t('Confidential remarks for the organizers only.') . '</em>',
      '#rows' => 5,
    );

    $form['award'] = array(
      '#type' => 'checkbox',
      '#title' => iish_t('Should be considered for best paper?'),
    );

    $reviewCriteria = CachedConferenceApi::getReviewCriteria();
    foreach ($reviewCriteria as $criteria) {
      $form['score_' . $criteria->getId()] = array(
        '#type' => 'radios',
        '#title' => iish_t($criteria->getName()),
        '#required' => TRUE,
        '#options' => ConferenceMisc::getScoreRadioOptions(),
        '#attributes' => array('class' => array('score-radios')),
      );
    }

    $form['submit-review'] = array(
      '#type' => 'submit',
      '#name' => 'submit-review',
      '#value' => iish_t('Submit review'),
    );

    return $form;
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $paperReview = $form_state->get('paper_review');

    $totalScore = 0;
    $reviewCriteria = CachedConferenceApi::getReviewCriteria();
    foreach ($reviewCriteria as $criteria) {
      $score = $form_state->getValue('score_' . $criteria->getId());
      $totalScore += $score;

      $paperReviewScore = new PaperReviewScoreApi();
      $paperReviewScore->setPaperReview($paperReview);
      $paperReviewScore->setCriteria($criteria);
      $paperReviewScore->setScore($score);
      $paperReviewScore->save();
    }

    $paperReview->setReview($form_state->getValue('review'));
    $paperReview->setComments($form_state->getValue('comments'));
    $paperReview->setAward($form_state->getValue('award'));
    $paperReview->setAvgScore(round(
      $totalScore / count($reviewCriteria), 1, PHP_ROUND_HALF_UP));
    $paperReview->save();

    drupal_set_message(iish_t('Your review has been successfully submitted!'));
    $form_state->setRedirect('iish_conference_reviews.index');
  }
}
