<?php
namespace Drupal\iish_conference_preregistration\Form\Page;

use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Form\FormStateInterface;

use Drupal\iish_conference\API\LoginApi;
use Drupal\iish_conference\API\LoggedInUserDetails;
use Drupal\iish_conference_preregistration\Form\PreRegistrationState;

/**
 * The password page.
 */
class PasswordPage extends PreRegistrationPage {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'iish_conference_preregistration_password';
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
    $state = new PreRegistrationState($form_state);

    $form['login_with_password'] = array(
      '#type' => 'fieldset',
    );

    $form['login_with_password']['help_text'] = array(
      '#markup' => '<div class="bottommargin">' . iish_t('Please enter your password.') . '</div>',
    );

    $form['login_with_password']['email'] = array(
      '#type' => 'textfield',
      '#title' => 'E-mail',
      '#size' => 20,
      '#maxlength' => 100,
      '#default_value' => $state->getEmail(),
      '#attributes' => array(
        'readonly' => 'readonly',
        'class' => array('readonly-text')
      ),
    );

    $form['login_with_password']['password'] = array(
      '#type' => 'password',
      '#title' => 'Password',
      '#required' => TRUE,
      '#size' => 20,
      '#maxlength' => 50,
    );

    $this->buildNextButton($form['login_with_password'], 'password_next');

    if (\Drupal::moduleHandler()->moduleExists('iish_conference_lostpassword')) {
      $lostPasswordLink = Link::fromTextAndUrl(iish_t('Lost password'),
        Url::fromRoute('iish_conference_lostpassword.form'));

      $form['login_with_password']['lost_password'] = array(
        '#markup' => '<div class="largertopmargin">' . $lostPasswordLink->toString() . '</div>',
      );
    }

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
    $state = new PreRegistrationState($form_state);

    $loginApi = new LoginApi();
    $userStatus = $loginApi->login($state->getEmail(), $form_state->getValue('password'));

    switch ($userStatus) {
      case LoggedInUserDetails::USER_STATUS_EXISTS:
      case LoggedInUserDetails::USER_STATUS_EMAIL_DISCONTINUED:
        $this->nextPageName = PreRegistrationPage::PERSONAL_INFO;
        return;
      case LoggedInUserDetails::USER_STATUS_DISABLED:
      case LoggedInUserDetails::USER_STATUS_DELETED:
        drupal_set_message(iish_t('The account with the given email address is disabled.'), 'error');
        break;
      case LoggedInUserDetails::USER_STATUS_DOES_NOT_EXISTS:
        drupal_set_message(iish_t('Incorrect email / password combination.'), 'error');
    }

    $this->nextPageName = PreRegistrationPage::PASSWORD;
  }
}
