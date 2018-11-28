<?php
namespace Drupal\iish_conference_login_logout\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\iish_conference\API\LoggedInUserDetails;

/**
 * The logout form.
 */
class LogoutForm extends FormBase {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'conference_logout';
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
    $form['help-text'] = array(
      '#markup' => '<div class="bottommargin">'
        . iish_t('Are you sure you want to log out?')
        . '</div>',
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => iish_t('Logout'),
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
    LoggedInUserDetails::invalidateSession();
    $form_state->setRedirect('iish_conference_login_logout.login_form');
  }
}