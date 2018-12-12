<?php
namespace Drupal\iish_conference_preregistration\Form\Page;

use Drupal\Core\Form\FormStateInterface;

use Drupal\iish_conference\ConferenceMisc;
use Drupal\iish_conference_preregistration\Form\PreRegistrationState;
use Drupal\iish_conference_preregistration\Form\PreRegistrationUtils;

use Drupal\iish_conference\API\SettingsApi;
use Drupal\iish_conference\API\CRUDApiClient;
use Drupal\iish_conference\API\CachedConferenceApi;
use Drupal\iish_conference\API\LoggedInUserDetails;

use Drupal\iish_conference\API\Domain\ExtraApi;
use Drupal\iish_conference\API\Domain\CountryApi;
use Drupal\iish_conference\API\Domain\VolunteeringApi;
use Drupal\iish_conference\API\Domain\ParticipantDateApi;
use Drupal\iish_conference\API\Domain\ParticipantVolunteeringApi;

/**
 * The personal info page.
 */
class PersonalInfoPage extends PreRegistrationPage {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'iish_conference_preregistration_personal_info';
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
    $user = $state->getUser();
    $participant = $state->getParticipant();

    $showChairDiscussantPool = SettingsApi::getSetting(SettingsApi::SHOW_CHAIR_DISCUSSANT_POOL, 'bool');
    $showLanguageCoaching = SettingsApi::getSetting(SettingsApi::SHOW_LANGUAGE_COACH_PUPIL, 'bool');

    $allVolunteering = PreRegistrationUtils::getAllVolunteeringOfUser($state);
    $networkOptions = CRUDApiClient::getAsKeyValueArray(CachedConferenceApi::getNetworks());

    $state->setFormData(array('volunteering' => $allVolunteering));

    // + + + + + + + + + + + + + + + + + + + + + + + +
    // PERSONAL INFO

    $form['personal_info'] = array(
      '#type' => 'fieldset',
      '#title' => iish_t('Personal info'),
    );

    $form['personal_info']['firstname'] = array(
      '#type' => 'textfield',
      '#title' => iish_t('First name'),
      '#size' => 40,
      '#maxlength' => 255,
      '#required' => TRUE,
      '#default_value' => $user->getFirstName(),
    );

    $form['personal_info']['lastname'] = array(
      '#type' => 'textfield',
      '#title' => iish_t('Last name'),
      '#size' => 40,
      '#maxlength' => 255,
      '#required' => TRUE,
      '#default_value' => $user->getLastName(),
    );

    $form['personal_info']['gender'] = array(
      '#type' => 'select',
      '#title' => iish_t('Gender'),
      '#description' => iish_t('Choose \'Other\' if you do not wish to enter your gender.'),
      '#options' => ConferenceMisc::getGenders(),
      '#default_value' => $user->getGender(),
    );

    $form['personal_info']['organisation'] = array(
      '#type' => 'textfield',
      '#title' => iish_t('Organisation'),
      '#size' => 40,
      '#maxlength' => 255,
      '#required' => TRUE,
      '#default_value' => $user->getOrganisation(),
    );

    if (SettingsApi::getSetting(SettingsApi::SHOW_DEPARTMENT, 'bool')) {
      $form['personal_info']['department'] = array(
        '#type' => 'textfield',
        '#title' => iish_t('Department'),
        '#size' => 40,
        '#maxlength' => 255,
        '#required' => TRUE,
        '#default_value' => $user->getDepartment(),
      );
    }

    if (SettingsApi::getSetting(SettingsApi::SHOW_EDUCATION, 'bool')) {
      $form['personal_info']['education'] = array(
        '#type' => 'textfield',
        '#title' => iish_t('Education'),
        '#description' => iish_t('Please enter both the education and the university.'),
        '#size' => 40,
        '#maxlength' => 255,
        '#required' => TRUE,
        '#default_value' => $user->getEducation(),
      );
    }

    $form['personal_info']['email'] = array(
      '#type' => 'textfield',
      '#title' => iish_t('E-mail'),
      '#size' => 40,
      '#maxlength' => 100,
      '#default_value' => $user->getEmail(),
      '#attributes' => array(
        'readonly' => 'readonly',
        'class' => array('readonly-text')
      ),
    );

    if (SettingsApi::getSetting(SettingsApi::SHOW_AGE_RANGE, 'bool')) {
      $form['personal_info']['age_range'] = array(
        '#type' => 'select',
        '#title' => iish_t('Age'),
        '#required' => TRUE,
        '#options' => CRUDApiClient::getAsKeyValueArray(CachedConferenceApi::getAgeRanges()),
        '#default_value' => $participant->getAgeRangeId(),
      );
    }

	if (SettingsApi::getSetting(SettingsApi::SHOW_OPT_IN, 'bool')) {
	  $form['personal_info']['opt_in'] = array(
		  '#type' => 'checkbox',
		  '#title' => iish_t('Check if you would like to receive communications (newsletters and calls for papers)'),
		  '#default_value' => ( $user->getId() === null ? true : $user->getOptIn() ),
	  );
	}

    if (SettingsApi::getSetting(SettingsApi::SHOW_STUDENT, 'bool')) {
      $form['personal_info']['student'] = array(
        '#type' => 'checkbox',
        '#title' => iish_t('Please check if you are a (PhD) student'),
        '#default_value' => $participant->getStudent(),
      );
    }

    if (SettingsApi::getSetting(SettingsApi::SHOW_CV, 'bool')) {
      $form['personal_info']['cv'] = array(
        '#type' => 'textarea',
        '#title' => iish_t('Curriculum Vitae'),
        '#description' => '<em>' . iish_t('(optional, max. 200 words)') . '</em>',
        '#rows' => 2,
        '#required' => SettingsApi::getSetting(SettingsApi::REQUIRED_CV, 'bool'),
        '#default_value' => $user->getCv(),
      );
    }

    // + + + + + + + + + + + + + + + + + + + + + + + +
    // EXTRA'S

    $extras = ExtraApi::getOnlyPreRegistrationFiltered(CachedConferenceApi::getExtras());
    if (count($extras) > 0) {
      foreach ($extras as $extra) {
        $form['extras']['extras_' . $extra->getId()] = array(
          '#title' => $extra->getTitle(),
          '#type' => 'checkboxes',
          '#description' => $extra->getSecondDescription(),
          '#options' => array($extra->getId() => $extra->getDescription()),
          '#default_value' => $participant->getExtrasId(),
        );
      }
    }

    // + + + + + + + + + + + + + + + + + + + + + + + +
    // ADDRESS

    $form['address'] = array(
      '#type' => 'fieldset',
      '#title' => iish_t('Address'),
    );

    $form['address']['city'] = array(
      '#type' => 'textfield',
      '#title' => iish_t('City'),
      '#size' => 40,
      '#maxlength' => 255,
      '#required' => TRUE,
      '#default_value' => $user->getCity(),
    );

    $form['address']['country'] = array(
      '#type' => 'select',
      '#title' => iish_t('Country'),
      '#options' => CRUDApiClient::getAsKeyValueArray(CachedConferenceApi::getCountries()),
      '#required' => TRUE,
      '#default_value' => ($user->getCountryId() !== null)
        ? $user->getCountryId()
        : CountryApi::getCountryOfEvent()->getId(),
    );

    // + + + + + + + + + + + + + + + + + + + + + + + +
    // COMMUNICATION MEANS

    $form['communication_means'] = array(
      '#type' => 'fieldset',
      '#title' => iish_t('Communication Means'),
    );

    $form['communication_means']['phone'] = array(
      '#type' => 'textfield',
      '#title' => iish_t('Phone number'),
      '#size' => 40,
      '#maxlength' => 100,
      '#default_value' => $user->getPhone(),
    );

    $form['communication_means']['mobile'] = array(
      '#type' => 'textfield',
      '#title' => iish_t('Mobile number'),
      '#size' => 40,
      '#maxlength' => 100,
      '#default_value' => $user->getMobile(),
    );

    $form['communication_means']['extra_info'] = array(
      '#markup' => '<span class="extra_info">' .
        iish_t('Please enter international numbers (including country prefix etc.)') .
        '</span>',
    );

    // + + + + + + + + + + + + + + + + + + + + + + + +
    // CHAIR / DISCUSSANT POOL

    if ($showChairDiscussantPool) {
      $chairVolunteering =
        ParticipantVolunteeringApi::getAllNetworksForVolunteering($allVolunteering, VolunteeringApi::CHAIR);
      $chairOptions = array_keys(CRUDApiClient::getAsKeyValueArray($chairVolunteering));

      $form['chair_discussant_pool'] = array(
        '#type' => 'fieldset',
        '#title' => iish_t('Chair / discussant pool'),
      );

      $form['chair_discussant_pool']['volunteerchair'] = array(
        '#type' => 'checkbox',
        '#title' => iish_t('I would like to volunteer as Chair'),
        '#default_value' => count($chairOptions) > 0,
      );

      $form['chair_discussant_pool']['volunteerchair_networks'] = array(
        '#type' => 'select',
        '#options' => CRUDApiClient::getAsKeyValueArray(CachedConferenceApi::getNetworks()),
        '#multiple' => TRUE,
        '#size' => 4,
        '#description' => '<i>' . iish_t('Use CTRL key to select multiple networks.') . '</i>',
        '#states' => array(
          'visible' => array(
            ':input[name="volunteerchair"]' => array('checked' => TRUE),
          ),
        ),
        '#default_value' => $chairOptions,
      );

      PreRegistrationUtils::hideAndSetDefaultNetwork($form['chair_discussant_pool']['volunteerchair_networks']);

      // + + + + + + + + + + + + + + + + + + + + + + + +

      $discussantVolunteering =
        ParticipantVolunteeringApi::getAllNetworksForVolunteering($allVolunteering, VolunteeringApi::DISCUSSANT);
      $discussantOptions = array_keys(CRUDApiClient::getAsKeyValueArray($discussantVolunteering));

      $form['chair_discussant_pool']['volunteerdiscussant'] = array(
        '#type' => 'checkbox',
        '#title' => iish_t('I would like to volunteer as Discussant'),
        '#default_value' => count($discussantOptions) > 0,
      );

      $form['chair_discussant_pool']['volunteerdiscussant_networks'] = array(
        '#type' => 'select',
        '#options' => CRUDApiClient::getAsKeyValueArray(CachedConferenceApi::getNetworks()),
        '#multiple' => TRUE,
        '#size' => 4,
        '#description' => '<i>' . iish_t('Use CTRL key to select multiple networks.') . '</i>',
        '#states' => array(
          'visible' => array(
            ':input[name="volunteerdiscussant"]' => array('checked' => TRUE),
          ),
        ),
        '#default_value' => $discussantOptions,
      );

      PreRegistrationUtils::hideAndSetDefaultNetwork($form['chair_discussant_pool']['volunteerdiscussant_networks']);
    }

    // + + + + + + + + + + + + + + + + + + + + + + + +
    // ENGLISH LANGUAGE COACH

    if ($showLanguageCoaching) {
      $coachVolunteering =
        ParticipantVolunteeringApi::getAllNetworksForVolunteering($allVolunteering, VolunteeringApi::COACH);
      $pupilVolunteering =
        ParticipantVolunteeringApi::getAllNetworksForVolunteering($allVolunteering, VolunteeringApi::PUPIL);

      $coachOptions = array_keys(CRUDApiClient::getAsKeyValueArray($coachVolunteering));
      $pupilOptions = array_keys(CRUDApiClient::getAsKeyValueArray($pupilVolunteering));

      $defaultValue = '';
      if (count($coachOptions) > 0) {
        $defaultValue = 'coach';
      }
      else {
        if (count($pupilOptions) > 0) {
          $defaultValue = 'pupil';
        }
      }

      $form['english_language_coach'] = array(
        '#type' => 'fieldset',
        '#title' => iish_t('English Language Coach'),
      );

      $form['english_language_coach']['coachpupil'] = array(
        '#type' => 'radios',
        '#options' => ConferenceMisc::getLanguageCoachPupils(),
        '#default_value' => $defaultValue,
      );

      $form['english_language_coach']['coachpupil_networks'] = array(
        '#type' => 'select',
        '#options' => $networkOptions,
        '#multiple' => TRUE,
        '#size' => 4,
        '#description' => '<i>' . iish_t('Use CTRL key to select multiple networks.') . '</i>',
        '#states' => array(
          'visible' => array(
            array(':input[name="coachpupil"]' => array('value' => 'coach')),
            'or',
            array(':input[name="coachpupil"]' => array('value' => 'pupil')),
          )
        ),
        '#default_value' => (count($coachOptions) > 0) ? $coachOptions : $pupilOptions,
      );

      PreRegistrationUtils::hideAndSetDefaultNetwork($form['english_language_coach']['coachpupil_networks']);
    }

    // + + + + + + + + + + + + + + + + + + + + + + + +

    $this->buildNextButton($form, 'personalinfo_next');

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
    if (SettingsApi::getSetting(SettingsApi::SHOW_CHAIR_DISCUSSANT_POOL, 'bool')) {
      // Make sure that when a chair is checked, a network is chosen as well
      if ($form_state->getValue('volunteerchair')) {
        if (count($form_state->getValue('volunteerchair_networks')) === 0) {
          $form_state->setErrorByName('volunteerchair',
            iish_t('Please select a network or uncheck the field \'I would like to volunteer as Chair\'.')
          );
        }
      }

      // Make sure that when a discussant is checked, a network is chosen as well
      if ($form_state->getValue('volunteerdiscussant')) {
        if (count($form_state->getValue('volunteerdiscussant_networks')) === 0) {
          $form_state->setErrorByName('volunteerdiscussant',
            iish_t('Please select a network or uncheck the field \'I would like to volunteer as Discussant\'.')
          );
        }
      }
    }

    if (SettingsApi::getSetting(SettingsApi::SHOW_LANGUAGE_COACH_PUPIL, 'bool')) {
      // Make sure that when a language coach or pupil is checked, a network is chosen as well
      if (in_array($form_state->getValue('coachpupil'), array(
        'coach',
        'pupil'
      ))) {
        if (count($form_state->getValue('coachpupil_networks')) === 0) {
          $form_state->setErrorByName('coachpupil',
            iish_t('Please select a network or select \'not applicable\' at English language coach.')
          );
        }
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
    $state = new PreRegistrationState($form_state);
    $user = $state->getUser();
    $participant = $state->getParticipant();

    // First save the user
    $user->setEmail($form_state->getValue('email'));
    $user->setFirstName($form_state->getValue('firstname'));
    $user->setLastName($form_state->getValue('lastname'));
    $user->setGender($form_state->getValue('gender'));
    $user->setOrganisation($form_state->getValue('organisation'));
    $user->setCity($form_state->getValue('city'));
    $user->setCountry($form_state->getValue('country'));
    $user->setPhone($form_state->getValue('phone'));
    $user->setMobile($form_state->getValue('mobile'));

    if (SettingsApi::getSetting(SettingsApi::SHOW_DEPARTMENT, 'bool')) {
      $user->setDepartment($form_state->getValue('department'));
    }

    if (SettingsApi::getSetting(SettingsApi::SHOW_EDUCATION, 'bool')) {
      $user->setEducation($form_state->getValue('education'));
    }

	if (SettingsApi::getSetting(SettingsApi::SHOW_OPT_IN, 'bool')) {
	  $user->setOptIn($form_state->getValue('opt_in'));
	}

	if (SettingsApi::getSetting(SettingsApi::SHOW_CV, 'bool')) {
	  $user->setCv($form_state->getValue('cv'));
	}

    $user->save();

    // Then save the participant
    if (SettingsApi::getSetting(SettingsApi::SHOW_STUDENT, 'bool')) {
      $participant->setStudent($form_state->getValue('student'));
    }
    $participant->setUser($user);

    if (SettingsApi::getSetting(SettingsApi::SHOW_AGE_RANGE, 'bool')) {
      $participant->setAgeRange($form_state->getValue('age_range'));
    }

    // Don't forget the extras for this participant
    $extras = array();
    foreach (ExtraApi::getOnlyPreRegistration(CachedConferenceApi::getExtras()) as $extra) {
      if (isset($form_state->getValue('extras_' . $extra->getId())[$extra->getId()])) {
        $value = $form_state->getValue('extras_' . $extra->getId())[$extra->getId()];
        if ($extra->getId() == $value) {
          $extras[] = $extra->getId();
        }
      }
    }
    $participant->setExtras($extras);

    $participant->save();
    LoggedInUserDetails::setCurrentlyLoggedIn($user);

    // Then the volunteering options (chair / discussant / language coach / language pupil)
    $data = $state->getFormData();
    $allToDelete = $data['volunteering'];

    if (SettingsApi::getSetting(SettingsApi::SHOW_CHAIR_DISCUSSANT_POOL, 'bool')) {
      if ($form_state->getValue('volunteerchair')) {
        $this->saveVolunteering($participant, VolunteeringApi::CHAIR,
          $form_state->getValue('volunteerchair_networks'), $allToDelete);
      }
      if ($form_state->getValue('volunteerdiscussant')) {
        $this->saveVolunteering($participant, VolunteeringApi::DISCUSSANT,
          $form_state->getValue('volunteerdiscussant_networks'), $allToDelete);
      }
    }

    if (SettingsApi::getSetting(SettingsApi::SHOW_LANGUAGE_COACH_PUPIL, 'bool')) {
      if ($form_state->getValue('coachpupil') == 'coach') {
        $this->saveVolunteering($participant, VolunteeringApi::COACH,
          $form_state->getValue('coachpupil_networks'), $allToDelete);
      }
      if ($form_state->getValue('coachpupil') == 'pupil') {
        $this->saveVolunteering($participant, VolunteeringApi::PUPIL,
          $form_state->getValue('coachpupil_networks'), $allToDelete);
      }
    }

    // Delete all previously saved volunteering choices that were not chosen again
    foreach ($allToDelete as $instance) {
      $instance->delete();
    }

    // Find out which page to go to next
    $typeOfRegistrationPage = new TypeOfRegistrationPage();
    $commentsPage = new CommentsPage();

    if ($typeOfRegistrationPage->isOpen()) {
      $this->nextPageName = PreRegistrationPage::TYPE_OF_REGISTRATION;
    }
    else {
      if ($commentsPage->isOpen()) {
        $this->nextPageName = PreRegistrationPage::COMMENTS;
      }
      else {
        $this->nextPageName = PreRegistrationPage::CONFIRM;
      }
    }
  }

  /**
   * Look up which networks were chosen by the participant for the selected volunteering type.
   * If the network was chosen before, remove the instance from the list 'to be removed'.
   * If the network was not chosen before, create a new instance and save it.
   *
   * @param ParticipantDateApi|int $participant The participant in question
   * @param int $volunteeringId The volunteering type id
   * @param int[] $networkValues The chosen network ids
   * @param ParticipantVolunteeringApi[] $allToDelete The ParticipantVolunteeringApi previously saved
   */
  private function saveVolunteering($participant, $volunteeringId, array $networkValues,
                                    array &$allToDelete) {
    foreach ($networkValues as $networkId => $network) {
      if ($networkId == $network) {
        $foundInstance = FALSE;
        foreach ($allToDelete as $key => $instance) {
          if (($instance->getVolunteeringId() == $volunteeringId) && ($instance->getNetworkId() == $networkId)) {
            $foundInstance = TRUE;
            unset($allToDelete[$key]);
            break;
          }
        }

        if (!$foundInstance) {
          $participantVolunteering = new ParticipantVolunteeringApi();
          $participantVolunteering->setParticipantDate($participant);
          $participantVolunteering->setVolunteering($volunteeringId);
          $participantVolunteering->setNetwork($networkId);
          $participantVolunteering->save();
        }
      }
    }
  }
}
