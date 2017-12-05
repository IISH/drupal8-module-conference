<?php
namespace Drupal\iish_conference_lostpassword\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\iish_conference_lostpassword\API\LostPasswordApi;

/**
 * The lost password form.
 */
class LostPasswordForm extends FormBase {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'conference_lost_password';
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['info'] = array(
      '#markup' => '<div class="bottommargin">'
        . iish_t('Please enter your e-mail address.') . '<br />'
        . iish_t('We will send you a new link you can use to confirm your email.') . '<br />'
        . iish_t('After confirmation you will receive a new password.') . '</div>',
    );

    $form['email'] = array(
      '#type' => 'textfield',
      '#title' => iish_t('E-mail'),
      '#size' => 20,
      '#maxlength' => 100,
      '#required' => TRUE,
      '#prefix' => '<div class="container-inline bottommargin">',
      '#suffix' => '</div>',
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => 'Send'
    );

    return $form;
  }

  /**
   * Form validation handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $email = strtolower(trim($form_state->getValue('email')));

    if (!\Drupal::service('email.validator')->isValid($email)) {
      $form_state->setErrorByName('email', iish_t('The email address appears to be invalid.'));
    }
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
    $email = strtolower(trim($form_state->getValue('email')));

    $lostPasswordApi = new LostPasswordApi();
    $status = $lostPasswordApi->lostPassword($email);

    if (is_int($status)) {
      switch ($status) {
        case LostPasswordApi::USER_STATUS_EXISTS:
          drupal_set_message(iish_t('We have received your request for a new password. ' .
            'We have sent you an e-mail you have to confirm before we will send you a new password.'),
            'status');
          break;
        case LostPasswordApi::USER_STATUS_DISABLED:
          drupal_set_message(iish_t('Account is disabled.'), 'error');
          break;
        case LostPasswordApi::USER_STATUS_DELETED:
          drupal_set_message(iish_t('Account is blocked.'), 'error');
          break;
        default:
          drupal_set_message(iish_t('We could not find this e-mail address.'), 'error');
      }
    }
    else {
      drupal_set_message(iish_t('We failed to process your new password request, please try again later.' .
        'We are sorry for the inconvenience.'), 'error');
    }
  }
}