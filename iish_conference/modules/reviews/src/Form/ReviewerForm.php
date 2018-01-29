<?php
namespace Drupal\iish_conference_reviews\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use Drupal\iish_conference\API\CRUDApiMisc;
use Drupal\iish_conference\API\Domain\ReviewerApi;
use Drupal\iish_conference\API\Domain\UserApi;

/**
 * The reviewer form.
 */
class ReviewerForm extends FormBase {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'conference_reviewer';
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param UserApi $user The user.
   * @param int $reviewerId The reviewer id.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $user = NULL, $reviewerId = NULL) {
    if ($user === NULL || $reviewerId === NULL) {
      drupal_set_message(iish_t('This is not a valid link!'), 'error');
      return array();
    }

    $reviewer = CRUDApiMisc::getById(new ReviewerApi(), $reviewerId);
    if ($reviewer === NULL || ($reviewer->getUserId() !== $user->getId())) {
      drupal_set_message(iish_t('This is not a valid link!'), 'error');
      return array();
    }

    if ($reviewer->hasConfirmed() !== NULL) {
      drupal_set_message(iish_t('You have already replied!'), 'error');
      return array();
    }

    $form_state->set('reviewer', $reviewer);

    $form['reviewer'] = array(
      '#type' => 'radios',
      '#name' => 'reviewer',
      '#required' => TRUE,
      '#options' => array(
        'yes' => iish_t('I would like to participate as a reviewer'),
        'no' => iish_t('I would NOT like to participate as a reviewer'),
      )
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#name' => 'submit',
      '#value' => iish_t('Submit'),
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
    $reviewer = $form_state->get('reviewer');
    $reviewer->setConfirmed($form_state->getValue('reviewer') === 'yes');
    $reviewer->save();

    drupal_set_message(iish_t('Thank you! The organizers have been informed of your decision!'));
    $form_state->setRedirect('<front>');
  }
}
