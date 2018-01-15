<?php
namespace Drupal\iish_conference_finalregistration\Form;

use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Render;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use Drupal\iish_conference\API\SettingsApi;
use Drupal\iish_conference\API\LoggedInUserDetails;
use Drupal\iish_conference\API\CachedConferenceApi;
use Drupal\iish_conference\API\Domain\FeeStateApi;

use Drupal\iish_conference\ConferenceMisc;
use Drupal\iish_conference\ConferenceTrait;
use Drupal\iish_conference_finalregistration\API\PayWayMessage;
use Drupal\iish_conference_finalregistration\Form\Page\MainPage;
use Drupal\iish_conference_finalregistration\Form\Page\OverviewPage;

use Symfony\Component\HttpFoundation\Response;

/**
 * The final registration form.
 */
class FinalRegistrationForm extends FormBase {
  use ConferenceTrait;

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'conference_final_registration';
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
    // Make sure we always start at the main stage
    if ($form_state->get('page') === NULL) {
      $form_state->set('page', 'main');
    }

    // redirect to login page
    if ($this->redirectIfNotLoggedIn() === TRUE) {
      return array();
    }

    // TODO Should we only allow payments of finished pre-registrations?
    // if (!LoggedInUserDetails::isAParticipant()) {
    if (!LoggedInUserDetails::isAParticipant() && !LoggedInUserDetails::isAParticipantWithoutConfirmation()) {
      $preRegistrationLink = Link::fromTextAndUrl(iish_t('pre-registration form'),
        Url::fromRoute('iish_conference_preregistration.form'));

      drupal_set_message(iish_t('You are not registered for the @conference conference. Please go to @link.',
        array(
          '@conference' => CachedConferenceApi::getEventDate()->getLongNameAndYear(),
          '@link' => $preRegistrationLink->toString()
        )), 'warning');

      return $form;
    }

    if (!SettingsApi::getSetting(SettingsApi::FINAL_REGISTRATION_LASTDATE, 'lastdate')) {
      drupal_set_message(iish_t('The final registration is closed.'), 'warning');

      return $form;
    }

    // Get fee amount information
    $participant = LoggedInUserDetails::getParticipant();
    $feeAmounts = $participant->getFeeAmounts();

    if (count($feeAmounts) === 0) {
      drupal_set_message(iish_t('Something is wrong with your fee, please contact :email.',
        array(
          ':email' => ConferenceMisc::encryptEmailAddress(
            SettingsApi::getSetting(SettingsApi::DEFAULT_ORGANISATION_EMAIL))
        )), 'error');

      return $form;
    }

    if (SettingsApi::getSetting(SettingsApi::SHOW_ACCOMPANYING_PERSONS, 'bool')) {
      $accompanyingPersonsFeeState = FeeStateApi::getAccompanyingPersonFee();

      if (($accompanyingPersonsFeeState === NULL) || (count($accompanyingPersonsFeeState->getFeeAmounts()) === 0)) {
        drupal_set_message(iish_t('Something is wrong with your fee, please contact :email .',
          array(
            ':email' => ConferenceMisc::encryptEmailAddress(
              SettingsApi::getSetting(SettingsApi::DEFAULT_ORGANISATION_EMAIL))
          )), 'error');

        return $form;
      }
    }

    if ($participant->getPaymentId()) {
      $orderDetails = new PayWayMessage(array('orderid' => $participant->getPaymentId()));
      $order = $orderDetails->send('orderDetails');

      if (!empty($order)) {
        if ($order->get('payed') == 1) {

	        $rendered_message = \Drupal\Core\Render\Markup::create(
	            iish_t('You already finished the final registration for the @conference.', array(
			        '@conference' => CachedConferenceApi::getEventDate()->getLongNameAndYear()
		        ))
	            . ' ' .
	            iish_t('(See your personal page.)')
		        . '<br />' .
		        iish_t('If you have questions please contact the secretariat at :email .',
			        array(
				        ':email' => ConferenceMisc::encryptEmailAddress(SettingsApi::getSetting(SettingsApi::DEFAULT_ORGANISATION_EMAIL))
			        ))
			);

          drupal_set_message($rendered_message);

          return $form;
        }
        else {
          if ($order->get('paymentmethod') == 1) {
            $bankTransferLink = Link::fromTextAndUrl(iish_t('Click here'),
              Url::fromRoute('iish_conference_finalregistration.bank_transfer'));

            $form['will-pay-by-bank'] = array(
              '#markup' =>
                '<div class="eca_warning">'
                . iish_t('Last time you chose to finish your final registration by bank transfer. '
                . '@link for the bank transfer information. '
                . 'Please continue if you want to choose a different payment method.',
                  array('@link' => $bankTransferLink->toString()))
                . '</div>',
            );
          }
        }
      }
      else {
        drupal_set_message(iish_t('Currently it is not possible to proceed with the final registration. ' .
          'Please try again later...'), 'error');

        return $form;
      }
    }

    return $this->getFormForCurrentStage($form, $form_state);
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
    if ($form_state->get('page') === 'overview') {
      $trigger = $this->getTriggeringElement($form_state);
      if (($trigger !== NULL) && ($trigger['#name'] !== 'back')) {
        $page = new OverviewPage();
        $page->validateForm($form, $form_state);
      }
    }
    else {
      $page = new MainPage();
      $page->validateForm($form, $form_state);
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
    $trigger = $this->getTriggeringElement($form_state);
    if ($trigger !== NULL) {
      if ($form_state->get('page') === 'overview') {
        if ($trigger['#name'] == 'back') {
          $form_state->set('page', 'main');
          $form_state->setRebuild();
          $form_state->addRebuildInfo('copy', array('#build_id' => TRUE));
        }
        else {
          $page = new OverviewPage();
          $page->submitForm($form, $form_state);
        }
      }
      else {
        if ($trigger['#name'] == 'next') {
          $page = new MainPage();
          $page->submitForm($form, $form_state);

          $form_state->set('page', 'overview');
          $form_state->setRebuild();
          $form_state->addRebuildInfo('copy', array('#build_id' => TRUE));
        }
      }
    }
  }

  /**
   * Returns the form based on the current page of the user.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array $form
   *    The form structure.
   */
  private function getFormForCurrentStage(array $form, FormStateInterface $form_state) {
    $page = NULL;

    if ($form_state->get('page') === 'overview') {
      $form['#theme'] = 'iish_conference_finalregistration_overview';
      $page = new OverviewPage();
    }
    else {
      $form['#theme'] = 'iish_conference_finalregistration_main';
      $page = new MainPage();
    }

    return $page->buildForm($form, $form_state);
  }

  /**
   * Gets the REAL form element that triggered submission.
   * Make sure the triggering element was REALLY triggered.
   * On a re-POST when there is no trigger (form page was cached),
   * Drupal picks the first submit button found
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *    An associative array containing the structure of the form.
   * @return array|null
   *    The form element that triggered submission, of NULL if there is none.
   */
  private function getTriggeringElement(FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    if (isset($form_state->getUserInput()[$trigger['#name']])) {
      return $trigger;
    }
    return null;
  }
}
