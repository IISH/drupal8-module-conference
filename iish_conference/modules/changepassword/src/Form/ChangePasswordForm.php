<?php
namespace Drupal\iish_conference_changepassword\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use Drupal\iish_conference\API\LoggedInUserDetails;
use Drupal\iish_conference\ConferenceTrait;
use Drupal\iish_conference_changepassword\API\ChangePasswordApi;

/**
 * The change password form.
 */
class ChangePasswordForm extends FormBase {
  use ConferenceTrait;

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'conference_change_password';
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
    if ($this->redirectIfNotLoggedIn() === TRUE) {
      return array();
    }

    $form['hint'] = array(
      '#markup' => '<div class="topmargin">'
        . iish_t('Please enter your new password twice.')
        . '</div>',
    );

    $form['new_password'] = array(
      '#type' => 'password',
      '#title' => iish_t('New password'),
      '#size' => 20,
      '#maxlength' => 50,
      '#required' => TRUE,
      '#prefix' => '<div class="iishconference_container_inline">',
      '#suffix' => '</div>',
    );

    $form['confirm_password'] = array(
      '#type' => 'password',
      '#title' => iish_t('Confirm new password'),
      '#size' => 20,
      '#maxlength' => 50,
      '#required' => TRUE,
      '#prefix' => '<div class="iishconference_container_inline">',
      '#suffix' => '</div>',
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => iish_t('Change'),
    );

    $form['warning'] = array(
      '#markup' => '<div class="eca_warning topmargin">'
        . iish_t('The new password must be at least 8 characters long and 
        contain at least one lowercase character, one upper case character 
        and one digit.') . '</div>',
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
    $error_message = iish_t('The new password must be at least 8 characters long and contain at least ' .
      'one lowercase character, one upper case character and one digit.');

    // check length of new password
    if (strlen($form_state->getValue('new_password')) < 8) {
      $form_state->setErrorByName('new_password', $error_message);
    }

    // check if the new passwords are equal
    if ($form_state->getValue('new_password') != $form_state->getValue('confirm_password')) {
      $form_state->setErrorByName('confirm_password', iish_t('The confirm password is not equal to the new password.'));
    }

    // check if new passwords contain at least one lowercase, one uppercase, one digit
    if (!ChangePasswordApi::isPasswordValid($form_state->getValue('new_password'))) {
      $form_state->setErrorByName('new_password', $error_message);
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
    $changePasswordApi = new ChangePasswordApi();
    $success = $changePasswordApi->changePassword(
      LoggedInUserDetails::getId(),
      $form_state->getValue('new_password'),
      $form_state->getValue('confirm_password')
    );

    if ($success) {
      drupal_set_message(iish_t('Password is successfully changed!'), 'status');
    }
    else {
      drupal_set_message(iish_t('We failed to either change your password ' .
        'or to send you an email, please try again later.'), 'error');
    }
  }
}