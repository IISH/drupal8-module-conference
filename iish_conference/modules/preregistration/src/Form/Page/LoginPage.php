<?php
namespace Drupal\iish_conference_preregistration\Form\Page;

use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Form\FormStateInterface;
use Drupal\iish_conference\API\SettingsApi;
use Drupal\iish_conference\ConferenceMisc;
use Drupal\iish_conference\API\CRUDApiMisc;
use Drupal\iish_conference\API\Domain\UserApi;
use Drupal\iish_conference_preregistration\Form\PreRegistrationState;

/**
 * The login page.
 */
class LoginPage extends PreRegistrationPage {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'iish_conference_preregistration_login';
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
    $form['login'] = array(
      '#type' => 'fieldset',
    );

    $form['login']['help_text'] = array(
      '#markup' => '<div class="bottommargin">' . iish_t('Please enter your e-mail address.') . '</div>',
    );

    $form['login']['email'] = array(
      '#type' => 'textfield',
      '#title' => 'E-mail',
      '#required' => TRUE,
      '#size' => 30,
      '#maxlength' => 100,
    );

    $this->buildNextButton($form, 'login_next');

    $form['info_block'] = array(
      '#markup' => ConferenceMisc::getInfoBlock(),
    );

    $form['comments_block'] = array(
      '#markup' => '<div class="eca_warning">
			<br />
			<strong>' . iish_t('Comments') . '</strong>
			<br />
			<ol>
				<li>' .
        iish_t('Please disable (or minimize the size of) the cache in your browser (Internet Explorer, Firefox, Chrome)') . '</li>
				<li>' .
        iish_t('Use the back/next buttons in the form, do NOT use the browser back button') . '</li>
				<li>' .
        iish_t('Prepare your abstract beforehand. Do NOT type your abstract in the form field, but COPY it into the form field') . '</li>
				<li>' .
        iish_t('As long as you haven\'t confirmed your registration, you can always come back later to change your registration info') . '</li>
			</ol>
		</div>',
    );

    $privacyStatementUrl = trim(SettingsApi::getSetting(SettingsApi::URL_PRIVACY_STATEMENT));
    if ( SettingsApi::getSetting(SettingsApi::SHOW_PRIVACY_STATEMENT_ON_REGISTRATION_PAGE, 'bool') && $privacyStatementUrl != '' ) {
      $form['privacy_statement_block'] = array(
        '#markup' => '<div class="eca_warning">
        <br />'
         . Link::fromTextAndUrl(
            SettingsApi::getSetting(SettingsApi::CONFERENCE_CODE) . ' ' . iish_t('Privacy Statement')
            , Url::fromUri($privacyStatementUrl)
          )->toString()
         . '</div>',
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
    $email = trim($form_state->getValue('email'));
    if (!\Drupal::service('email.validator')->isValid($email)) {
      $form_state->setErrorByName('email', iish_t('The e-mail address appears to be invalid.'));
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
    $state = new PreRegistrationState($form_state);

    $email = strtolower(trim($form_state->getValue('email')));
    $state->setEmail($email);
    $user = CRUDApiMisc::getFirstWherePropertyEquals(new UserApi(), 'email', $email);

    // If the user is not found, then this must be a new user, otherwise he/she must login with password first
    if ($user === NULL) {
      $this->nextPageName = PreRegistrationPage::PERSONAL_INFO;
    }
    else {
      $this->nextPageName = PreRegistrationPage::PASSWORD;
    }
  }
}
