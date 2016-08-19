<?php
namespace Drupal\iish_conference_lostpassword\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use Drupal\iish_conference\EasyProtection;
use Drupal\iish_conference_lostpassword\API\ConfirmLostPasswordApi;

/**
 * The confirm lost password form.
 */
class ConfirmLostPasswordForm extends FormBase {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'conference_confirm_lost_password';
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
    $queryParameters = \Drupal::request()->query;

    $default_id = $queryParameters->get('id') ? EasyProtection::easyIntegerProtection($queryParameters->get('id')) : NULL;
    $default_code = $queryParameters->get('code') ? EasyProtection::easyStringProtection($queryParameters->get('code')) : NULL;

    $codeCheckOkay = FALSE;

    // Auto submit
    if (($default_id !== NULL) && ($default_id !== '') && ($default_code !== NULL) && ($default_code !== '')) {
      if (strtolower($_SERVER['REQUEST_METHOD']) === 'get') {
        $codeCheckOkay = $this->setMessage($default_id, $default_code);
      }
    }

    if (!$codeCheckOkay) {
      $form['info'] = array(
        '#markup' => '<div class="bottommargin">'
          . iish_t('Please enter the ID and CODE we have sent you via e-mail and click on Confirm.')
          . '<br />' . iish_t('After the CODE is confirmed we will send you a new password.')
          . '</div>',
      );

      $form['id'] = array(
        '#type' => 'textfield',
        '#title' => iish_t('ID'),
        '#size' => 20,
        '#maxlength' => 50,
        '#required' => TRUE,
        '#prefix' => '<div class="container-inline bottommargin">',
        '#suffix' => '</div>',
        '#default_value' => $default_id,
      );

      $form['code'] = array(
        '#type' => 'textfield',
        '#title' => iish_t('CODE'),
        '#size' => 20,
        '#maxlength' => 50,
        '#required' => TRUE,
        '#prefix' => '<div class="container-inline bottommargin">',
        '#suffix' => '</div>',
        '#default_value' => $default_code,
      );

      $form['submit'] = array(
        '#type' => 'submit',
        '#value' => 'Confirm'
      );
    }

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
    // RegExp only integers
    if (EasyProtection::easyIntegerProtection($form_state->getValue('id')) === NULL) {
      $form_state->setErrorByName('id', iish_t('The ID appears to be invalid.'));
    }

    // RegExp only digits and characters
    if (EasyProtection::easyStringProtection($form_state->getValue('code')) === '') {
      $form_state->setErrorByName('code', iish_t('The CODE appears to be invalid.'));
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
    $form_state->setRebuild();

    $id = EasyProtection::easyIntegerProtection($form_state->getValue('id'));
    $code = EasyProtection::easyStringProtection($form_state->getValue('code'));

    $this->setMessage($id, $code);
  }

  /**
   * Sets the message for lost password code checking.
   *
   * @param int $id The id of the user.
   * @param string $code The code to check for this id.
   *
   * @return bool Whether to return.
   */
  private function setMessage($id, $code) {
    $confirmLostPasswordApi = new ConfirmLostPasswordApi();
    $status = $confirmLostPasswordApi->confirmLostPassword($id, $code);

    switch ($status) {
      case ConfirmLostPasswordApi::ACCEPT:
        drupal_set_message(iish_t('We have sent you an e-mail with your new password.'), 'status');
        return TRUE;
      case ConfirmLostPasswordApi::PASSWORD_ALREADY_SENT:
        drupal_set_message(iish_t('We already sent you an email with your new password. Please check your email!'),
          'warning');
        return TRUE;
      case ConfirmLostPasswordApi::CODE_EXPIRED:
        drupal_set_message(iish_t('The CODE has been expired. Please request a new CODE.'), 'error');
        break;
      case ConfirmLostPasswordApi::ERROR:
        drupal_set_message(iish_t('We failed to send you an email with your new password. ' .
          'Please try again later. We are sorry for the inconvenience.'), 'error');
        break;
      default:
        drupal_set_message(iish_t('ID / CODE combination not found.'), 'error');
    }

    return FALSE;
  }
}