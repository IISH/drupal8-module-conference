<?php
namespace Drupal\iish_conference_preregistration\Form;

use Drupal\Core\Url;
use Drupal\Core\Link;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use Drupal\iish_conference\Markup\ConferenceHTML;
use Drupal\iish_conference\ConferenceMisc;

use Drupal\iish_conference\API\SettingsApi;
use Drupal\iish_conference\API\LoggedInUserDetails;
use Drupal\iish_conference\API\CachedConferenceApi;

/**
 * The pre registration form.
 */
class PreRegistrationForm extends FormBase {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'iish_conference_preregistration';
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
    $form['#attributes']['class'][] = 'iishconference_form';

    // Load ECA settings
    $closesOn = SettingsApi::getSetting(SettingsApi::PREREGISTRATION_LASTDATE);
    $startsOn = SettingsApi::getSetting(SettingsApi::PREREGISTRATION_STARTDATE);

    // Check if user is already registered for the current conference, if so, show message no changes possible
    if (LoggedInUserDetails::isLoggedIn() && LoggedInUserDetails::isAParticipant()) {
      if (\Drupal::moduleHandler()->moduleExists('iish_conference_personalpage')) {
        $form['ct1'] = array(
          '#markup' => '<div class="eca_warning">' .
            iish_t('You are already pre-registered for the @codeYear. It is not allowed to modify online ' .
              'your data after your data has been checked by the conference organization. If you would like to ' .
              'make some changes please send an e-mail to @code. Please go to your @link to check the data.',
              array(
                '@codeYear' => CachedConferenceApi::getEventDate()->getLongNameAndYear(),
                '@code' => CachedConferenceApi::getEventDate()->getEvent()->getShortName(),
                '@link' => Link::fromTextAndUrl(iish_t('personal page'), Url::fromRoute('iish_conference_personalpage.index'))->toString(),
              )) .
            '</div>',
        );
      }
      else {
        $form['ct1'] = array(
          '#markup' => '<div class="eca_warning">' .
            iish_t('You are already pre-registered for the @codeYear. It is not allowed to modify online ' .
              'your data after your data has been checked by the conference organization. If you would like to ' .
              'make some changes please send an e-mail to @code.',
              array(
                '@codeYear' => CachedConferenceApi::getEventDate()->getLongNameAndYear(),
                '@code' => CachedConferenceApi::getEventDate()->getEvent()->getShortName(),
              )) .
            '</div>',
        );
      }

      return $form;
    }

    // Check if preregistration is closed
    if (($closesOn !== NULL) && (strlen(trim($closesOn)) > 0) && (!ConferenceMisc::isOpenForLastDate(strtotime($closesOn)))) {
      $form['ct1'] = array(
        '#markup' =>
          '<div class="eca_warning">' .
          new ConferenceHTML(iish_t('Please note it is no longer possible to pre-register online. ' .
            'If you wish to register as listener, you can do so at the conference desk. ' .
            'If you have been in touch with the network chairs or session organizers about a paper proposal ' .
            'and still have to pre-register, please contact the secretariat at @email. ' .
            'It is still possible to do the Final Registration and Payment. ' .
            'If you haven\'t payed the conference fee, please do it as soon as possible.',
            array(
              '@email' => ConferenceMisc::encryptEmailAddress(
                SettingsApi::getSetting(SettingsApi::DEFAULT_ORGANISATION_EMAIL))
            ))) .
          '</div>',
      );

      return $form;
    }

    // Check if preregistration has started
    if (($startsOn !== NULL) && (strlen(trim($startsOn)) > 0) && (!ConferenceMisc::isOpenForStartDate(strtotime($startsOn)))) {
      $form['ct1'] = array(
        '#markup' =>
          '<div class="eca_warning">' .
          new ConferenceHTML(iish_t('The pre-registration for this conference has not started yet.')) .
          '</div>',
      );

      return $form;
    }

    // Now obtain the current page an build the form for that page
    $state = new PreRegistrationState($form_state);
    $page = $state->getCurrentPage();
    $form = $page->buildForm($form, $form_state);

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
    if ($this->getTriggeringElement($form_state) !== NULL) {
      $state = new PreRegistrationState($form_state);
      $page = $state->getCurrentPage();
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
    $state = new PreRegistrationState($form_state);
    $page = $state->getCurrentPage();
    $performRebuild = TRUE;

    $trigger = $this->getTriggeringElement($form_state);
    if ($trigger !== NULL) {
      // Call the next method
      $nav = isset($trigger['#nav']) ? $trigger['#nav'] : 'next';
      if ($nav == 'back') {
        $page->backForm($form, $form_state);
      } else {
        if ($nav == 'remove') {
          $page->deleteForm($form, $form_state);
        } else {
          $page->submitForm($form, $form_state);
        }
      }

      $nextPageName = $page->getNextPageName();
      if ($nextPageName != NULL) {
        $state->setNextPageName($nextPageName);
      }
      else {
        $performRebuild = FALSE;
      }
    }

    if ($performRebuild) {
      $form_state->setRebuild();
      $form_state->addRebuildInfo('copy', array('#build_id' => TRUE));
    }
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
