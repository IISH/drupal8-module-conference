<?php
namespace Drupal\iish_conference_finalregistration\Form\Page;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use Drupal\iish_conference\API\SettingsApi;
use Drupal\iish_conference\API\CRUDApiClient;
use Drupal\iish_conference\API\LoggedInUserDetails;
use Drupal\iish_conference\API\CachedConferenceApi;

use Drupal\iish_conference\API\Domain\ExtraApi;
use Drupal\iish_conference\API\Domain\CountryApi;
use Drupal\iish_conference\API\Domain\FeeStateApi;
use Drupal\iish_conference\API\Domain\FeeAmountApi;

/**
 * The final registration form main page.
 */
class MainPage extends FormBase {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'conference_final_registration_main';
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
    $participant = LoggedInUserDetails::getParticipant();
    $user = LoggedInUserDetails::getUser();

    $feeAmounts = $participant->getFeeAmounts();
    $days = CRUDApiClient::getAsKeyValueArray(CachedConferenceApi::getDays());

    // Start with the days
    if (SettingsApi::getSetting(SettingsApi::SHOW_DAYS, 'bool')) {
      $form['days_present'] = array(
        '#title' => iish_t('Days present'),
        '#type' => 'checkboxes',
        '#description' => iish_t('Please select the days you will be present.')
          . ' <span class="heavy">'
          . FeeAmountApi::getFeeAmountsDescription($feeAmounts)
          . '</span>.',
        '#options' => $days,
        '#default_value' => $user->getDaysPresentDayId(),
        '#required' => TRUE,
      );
    }

    // Only show invitation letter to participants in a not exempted country and participating in at least one session
    $countryEvent = CountryApi::getCountryOfEvent();
    $userCountry = $user->getCountry();
    $isCountryExempt = (($userCountry !== NULL) && ($countryEvent !== NULL) &&
      in_array($userCountry->getId(), $countryEvent->getExemptCountriesId()));

	//$isSessionParticipant = (count($user->getSessionParticipantInfo()) > 0);
	$isSessionParticipant = (count($user->getCombinedSessionParticipantInfo()) > 0);

    if (!$isCountryExempt && $isSessionParticipant) {
      $form['invitation_letter'] = array(
        '#title' => iish_t('Invitation letter'),
        '#type' => 'checkbox',
        '#description' => iish_t('Please check if you will need an invitation letter.'),
        '#default_value' => $participant->getInvitationLetter(),
      );

      $form['address'] = array(
        '#title' => iish_t('Address'),
        '#type' => 'textarea',
        '#description' => iish_t('Please enter the full address to which we have to send the invitation letter. ' .
          'This includes your name, address, zipcode and country.'),
        '#default_value' => $user->getAddress(),
        '#states' => array(
          'visible' => array(
            'input[name="invitation_letter"]' => array('checked' => TRUE),
          ),
        ),
      );
    }

    // Any extras from which the participant can choose?
    $extras = ExtraApi::getOnlyFinalRegistrationFiltered(CachedConferenceApi::getExtras());
    foreach ($extras as $extra) {
      $description = $extra->getSecondDescription();
      if ($extra->getAmount() > 0) {
        $description .= ' <span class="heavy">' . $extra->getAmountInFormat() . '</span>.';
      }

      $form['extras_' . $extra->getId()] = array(
        '#title' => $extra->getTitle(),
        '#type' => 'checkboxes',
        '#description' => trim($description),
        '#options' => array($extra->getId() => $extra->getDescription()),
        '#default_value' => $participant->getExtrasId(),
      );
    }

    // Only add accompanying persons if accepted
    if (SettingsApi::getSetting(SettingsApi::SHOW_ACCOMPANYING_PERSONS, 'bool')) {
      $accompanyingPersons = $participant->getAccompanyingPersons();
      $accompanyingPersonFeeState = FeeStateApi::getAccompanyingPersonFee();
      $accompanyingPersonFees = $accompanyingPersonFeeState->getFeeAmounts();

      // Always show add least one text field for participants to enter an accompanying person
      if ($form_state->get('num_persons') === NULL) {
        $form_state->set('num_persons', max(1, count($accompanyingPersons)));
      }

      $form['accompanying_persons'] = array(
        '#type' => 'container',
        '#prefix' => '<div id="accompanying-persons-wrapper">',
        '#suffix' => '</div>',
      );

      $title = iish_t('Accompanying persons');
      $description = iish_t('Please leave this field empty if you have no accompanying person.');
      $description .= ' <span class="heavy">' .
        FeeAmountApi::getFeeAmountsDescription($accompanyingPersonFees) . '</span>.';
      $form['accompanying_persons']['person']['#tree'] = TRUE;

      // Display all accompanying persons previously stored, unless the user deliberately removed some
      foreach ($accompanyingPersons as $i => $accompanyingPerson) {
        if ($i <= ($form_state->get('num_persons') - 1)) {
          $form['accompanying_persons']['person'][$i] = array(
            '#type' => 'textfield',
            '#size' => 40,
            '#maxlength' => 100,
            '#default_value' => $accompanyingPerson,
            '#title' => ($i === 0) ? $title : NULL,
            '#description' => ($i === ($form_state->get('num_persons') - 1)) ? trim($description) : NULL,
          );
        }
      }

      // Now display all additional empty text fields to enter accompanying persons, as many as requested by the user
      for ($i = count($accompanyingPersons); $i < $form_state->get('num_persons'); $i++) {
        $form['accompanying_persons']['person'][$i] = array(
          '#type' => 'textfield',
          '#size' => 40,
          '#maxlength' => 100,
          '#title' => ($i === 0) ? $title : NULL,
          '#description' => ($i === ($form_state->get('num_persons') - 1)) ? trim($description) : NULL,
        );
      }

      $form['accompanying_persons']['add_person'] = array(
        '#type' => 'submit',
        '#name' => 'add_person',
        '#value' => iish_t('Add one more person'),
        '#submit' => array(get_class() . '::addPerson'),
        '#limit_validation_errors' => array(),
        '#ajax' => array(
          'callback' => get_class() . '::callback',
          'wrapper' => 'accompanying-persons-wrapper',
          'progress' => array(
            'type' => 'throbber',
            'message' => iish_t('Please wait...'),
          ),
        ),
      );

      // Always display add least one text field to enter accompanying persons
      if ($form_state->get('num_persons') > 1) {
        $form['accompanying_persons']['remove_person'] = array(
          '#type' => 'submit',
          '#name' => 'remove_person',
          '#value' => iish_t('Remove the last person'),
          '#submit' => array(get_class() . '::removePerson'),
          '#limit_validation_errors' => array(),
          '#ajax' => array(
            'callback' => get_class() . '::callback',
            'wrapper' => 'accompanying-persons-wrapper',
            'progress' => array(
              'type' => 'throbber',
              'message' => iish_t('Please wait...'),
            ),
          ),
        );
      }
    }

    $form['next'] = array(
      '#type' => 'submit',
      '#name' => 'next',
      '#value' => iish_t('Next step'),
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
    // Make sure the values exists, if the user chooses to go back one step
    if ($form_state->getValue('invitation_letter') !== NULL) {
      if (($form_state->getValue('invitation_letter') === 1) &&
        (strlen(trim($form_state->getValue('address'))) === 0)
      ) {
        $form_state->setErrorByName('address', iish_t('Please enter your address, so we can send the invitation letter to you.'));
      }
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
    $participant = LoggedInUserDetails::getParticipant();
    $user = LoggedInUserDetails::getUser();

    // Save days
    if (SettingsApi::getSetting(SettingsApi::SHOW_DAYS, 'bool')) {
      $days = array();
      foreach ($form_state->getValue('days_present') as $dayId => $day) {
        if ($dayId == $day) {
          $days[] = $dayId;
        }
      }
      $user->setDaysPresent($days);
    }
    else {
      $days = CachedConferenceApi::getDays();
      $user->setDaysPresent(CRUDApiClient::getIds($days));
    }

    // Save invitation letter info
    if ($form_state->getValue('invitation_letter')) {
      $participant->setInvitationLetter($form_state->getValue('invitation_letter'));
      $user->setAddress($form_state->getValue('address'));
    }

    // Save extras
    $extras = array();
    foreach (ExtraApi::getOnlyFinalRegistration(CachedConferenceApi::getExtras()) as $extra) {
      if (isset($form_state->getValue('extras_' . $extra->getId())[$extra->getId()])) {
        $value = $form_state->getValue('extras_' . $extra->getId())[$extra->getId()];
        if ($extra->getId() == $value) {
          $extras[] = $extra->getId();
        }
      }
    }
    $participant->setExtras($extras);

    // Save accompanying person(s) into the database
    if (SettingsApi::getSetting(SettingsApi::SHOW_ACCOMPANYING_PERSONS, 'bool')) {
      $accompanyingPersons = array();
      foreach ($form_state->getValue('person') as $accompanyingPerson) {
        $accompanyingPerson = trim($accompanyingPerson);
        if (strlen($accompanyingPerson) > 0) {
          $accompanyingPersons[] = $accompanyingPerson;
        }
      }
      $participant->setAccompanyingPersons($accompanyingPersons);

      // Reset the number of additional persons in form state
      $form_state->set('num_persons', NULL);
    }

    $user->save();
    $participant->save();
  }

  /**
   * Ajax handler, add an accompanying person.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function addPerson(array $form, FormStateInterface $form_state) {
    $form_state->set('num_persons', $form_state->get('num_persons') + 1);
    $form_state->setRebuild();
  }

  /**
   * Ajax handler, remove an accompanying person.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function removePerson(array $form, FormStateInterface $form_state) {
    $form_state->set('num_persons', $form_state->get('num_persons') - 1);
    $form_state->setRebuild();
  }

  /**
   * Ajax handler, callback part of form to render: the accompanying persons.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public static function callback(array $form, FormStateInterface $form_state) {
    return $form['accompanying_persons'];
  }
}
