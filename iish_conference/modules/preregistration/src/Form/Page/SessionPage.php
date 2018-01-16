<?php
namespace Drupal\iish_conference_preregistration\Form\Page;

use Drupal\Core\Form\FormStateInterface;

use Drupal\iish_conference\ConferenceMisc;
use Drupal\iish_conference\EasyProtection;

use Drupal\iish_conference_preregistration\Form\PreRegistrationState;
use Drupal\iish_conference_preregistration\Form\PreRegistrationUtils;

use Drupal\iish_conference\API\SettingsApi;
use Drupal\iish_conference\API\CRUDApiMisc;
use Drupal\iish_conference\API\CRUDApiClient;
use Drupal\iish_conference\API\ApiCriteriaBuilder;
use Drupal\iish_conference\API\CachedConferenceApi;

use Drupal\iish_conference\API\Domain\UserApi;
use Drupal\iish_conference\API\Domain\SessionApi;
use Drupal\iish_conference\API\Domain\ParticipantTypeApi;
use Drupal\iish_conference\API\Domain\SessionParticipantApi;

/**
 * The session page.
 */
class SessionPage extends PreRegistrationPage {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'iish_conference_preregistration_session';
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
    $session = $this->getSession($state);
    $data = array();

    // + + + + + + + + + + + + + + + + + + + + + + + +
    // SESSION

    $form['session'] = array(
      '#type' => 'fieldset',
      '#title' => iish_t('Session info')
    );

    if (!PreRegistrationUtils::useSessions()) {
      $form['session']['sessionname'] = array(
        '#type' => 'textfield',
        '#title' => iish_t('Session name'),
        '#size' => 40,
        '#required' => TRUE,
        '#maxlength' => 255,
        '#default_value' => $session->getName(),
      );

      if (SettingsApi::getSetting(SettingsApi::SHOW_SESSION_TYPES, 'bool')) {
        $sessionTypes = CachedConferenceApi::getSessionTypes();

        if (count($sessionTypes) > 0) {
          $form['session']['sessiontype'] = array(
            '#title' => iish_t('Session type'),
            '#type' => 'select',
            '#options' => CRUDApiClient::getAsKeyValueArray($sessionTypes),
            '#default_value' => $session->getTypeId(),
          );
        }

        if (SettingsApi::getSetting(SettingsApi::SHOW_OPTIONAL_SESSION_TYPE, 'bool')) {
          if (count($sessionTypes) > 0) {
            $form['session']['sessiontype']['#empty_option'] = iish_t('Something else');
          }

          $form['session']['sessiondifferenttype'] = array(
            '#type' => 'textfield',
            '#size' => 25,
            '#maxlength' => 50,
            '#default_value' => $session->getDifferentType(),
            '#states' => array(
              'visible' => array(
                'select[name="sessiontype"]' => array('value' => ''),
              ),
            ),
          );
        }
        else if (count($sessionTypes) > 0) {
          $form['session']['sessiontype']['#required'] = TRUE;
        }
      }

      $form['session']['sessionabstract'] = array(
        '#type' => 'textarea',
        '#title' => iish_t('Abstract'),
        '#description' => '<em>(' . iish_t('max. 1.000 words') . ')</em>',
        '#rows' => 3,
        '#required' => TRUE,
        '#default_value' => $session->getAbstr(),
      );

      $networkIds = $session->getNetworksId();
      $form['session']['sessioninnetwork'] = array(
        '#title' => iish_t('Network'),
        '#type' => 'select',
        '#options' => CRUDApiClient::getAsKeyValueArray(CachedConferenceApi::getNetworks()),
        '#required' => TRUE,
        '#size' => 4,
        '#default_value' => isset($networkIds[0]) ? $networkIds[0] : NULL,
      );

      PreRegistrationUtils::hideAndSetDefaultNetwork($form['session']['sessioninnetwork']);
    }
    else {
      $fields = array();

      $fields[] = array(
        'label' => 'Session name',
        'value' => $session->getName(),
      );

      if (SettingsApi::getSetting(SettingsApi::SHOW_SESSION_TYPES, 'bool')) {
        $fields[] = array(
          'label' => 'Session type',
          'value' => $session->getType(),
        );
      }

      $fields[] = array(
        'label' => 'Abstract',
        'value' => $session->getAbstr(),
        'newLine' => TRUE,
      );

      if (PreRegistrationUtils::showNetworks()) {
        $fields[] = array(
          'label' => 'Networks',
          'value' => implode(', ', $session->getNetworks())
        );
      }

      $form['session']['info'] = array(
        '#theme' => 'iish_conference_container',
        '#fields' => $fields,
        '#styled' => FALSE,
      );
    }

    // + + + + + + + + + + + + + + + + + + + + + + + +
    // SESSION PARTICIPANTS

    $sessionParticipants = PreRegistrationUtils::getSessionParticipantsAddedByUserForSession($state, $session);
    $users = SessionParticipantApi::getAllUsers($sessionParticipants);
    $data['session_participants'] = $sessionParticipants;

    $form['session_participants'] = array(
      '#type' => 'fieldset',
      '#title' => iish_t('Participants'),
    );

    $this->buildNextButton($form['session_participants'], 'session_participant', iish_t('New participant'));
    $form['session_participants']['session_participant']['suffix']['#markup'] = '<br /><br />';

    $printOr = TRUE;
    foreach ($users as $user) {
      if ($printOr) {
        $form['session_participants']['prefix']['#markup'] = ' &nbsp;' . iish_t('or') . '<br /><br />';
        $printOr = FALSE;
      }

      $roles = SessionParticipantApi::getAllTypesOfUserForSession(
        $sessionParticipants,
        $user->getId(),
        $session->getId()
      );

      $this->buildNextButton($form['session_participants'], 'session_participant_' . $user->getId(), iish_t('Edit'));
      $form['session_participants']['session_participant_' . $user->getId()]['suffix']['#markup'] = ' ' . $user->getFullName() . ' &nbsp;&nbsp; <em>(' .
        ConferenceMisc::getEnumSingleLine($roles) . ')</em><br /><br />';
    }

    // + + + + + + + + + + + + + + + + + + + + + + + +

    $this->buildPrevButton($form, 'session_prev', iish_t('Back'));

    if (!PreRegistrationUtils::useSessions()) {
      $this->buildNextButton($form, 'session_next', iish_t('Save session'));
    }

    // We can only remove a session if it has been persisted
    if (!PreRegistrationUtils::useSessions() && $session->isUpdate()) {
      $this->buildRemoveButton($form, 'session_remove', iish_t('Remove session'),
        iish_t('Are you sure you want to remove this session?'));
    }

    $state->setFormData($data);

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
    if (!PreRegistrationUtils::useSessions()) {
      $state = new PreRegistrationState($form_state);
      $session = $this->getSession($state);

      $props = new ApiCriteriaBuilder();
      $props
        ->eq('name', trim($form_state->getValue('sessionname')))
        ->eq('addedBy.id', $state->getUser()->getId());

      if ($session->isUpdate()) {
        $props->ne('id', $session->getId());
      }

      // Don't allow multiple sessions with the same name
      $sessions = SessionApi::getListWithCriteria($props->get());
      if ($sessions->getTotalSize() > 0) {
        $form_state->setErrorByName('sessionname', iish_t('You already created a session with the same name.'));
      }

      if (($form_state->getValue('sessiontype') == '') &&
        (strlen(trim($form_state->getValue('sessiondifferenttype'))) === 0))  {
        $form_state->setErrorByName('sessiontype', iish_t('Please enter a session type.'));
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
    $session = $this->getSession($state);

    if (!PreRegistrationUtils::useSessions()) {
      // Save session information
      $session->setName($form_state->getValue('sessionname'));
      $session->setAbstr($form_state->getValue('sessionabstract'));

      if (SettingsApi::getSetting(SettingsApi::SHOW_SESSION_TYPES, 'bool')) {
        $session->setType($form_state->getValue('sessiontype'));

        if (SettingsApi::getSetting(SettingsApi::SHOW_OPTIONAL_SESSION_TYPE, 'bool')) {
          $differentType = ($form_state->getValue('sessiontype') == '')
            ? $form_state->getValue('sessiondifferenttype') : NULL;
          $session->setDifferentType($differentType);
        }
      }

      $networkId = EasyProtection::easyIntegerProtection($form_state->getValue('sessioninnetwork'));
      $session->setNetworks(array($networkId));

      // Before we persist this data, is this a new session?
      $newSession = !$session->isUpdate();
      $session->save();

      // Also add the current user to the session as an organiser if this is a new session
      if ($newSession) {
        $organiser = new SessionParticipantApi();
        $organiser->setUser($user);
        $organiser->setSession($session);
        $organiser->setType(ParticipantTypeApi::ORGANIZER_ID);

        $organiser->save();
        drupal_set_message(iish_t('You are added as organizer to this session. ' .
          'Please add participants to the session.'), 'status');
      }
    }

    // Now find out if we have to add a participant or simply save the session
    $submitName = $form_state->getTriggeringElement()['#name'];

    // Move back to the 'type of registration' page, clean cached data
    if ($submitName === 'session_next') {
      $state->setMultiPageData(array());
      $this->nextPageName = PreRegistrationPage::TYPE_OF_REGISTRATION;
    }

    if ($submitName === 'session_participant') {
      $this->setSessionParticipant($state, $session, NULL);
      return;
    }

    if (strpos($submitName, 'session_participant_') === 0) {
      $id = EasyProtection::easyIntegerProtection(str_replace('session_participant_', '', $submitName));
      $this->setSessionParticipant($state, $session, $id);
      return;
    }
  }

  /**
   * Form back button submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function backForm(array &$form, FormStateInterface $form_state) {
    $state = new PreRegistrationState($form_state);
    $state->setMultiPageData(array());
    $this->nextPageName = PreRegistrationPage::TYPE_OF_REGISTRATION;
  }

  /**
   * Form delete button submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function deleteForm(array &$form, FormStateInterface $form_state) {
    if (!PreRegistrationUtils::useSessions()) {
      $state = new PreRegistrationState($form_state);
      $multiPageData = $state->getMultiPageData();

      $session = $multiPageData['session'];
      $session->delete();

      $state->setMultiPageData(array());
    }

    $this->nextPageName = PreRegistrationPage::TYPE_OF_REGISTRATION;
  }

  /**
   * Obtain the session from the state.
   * @param PreRegistrationState $state The state of the pre registration.
   * @return SessionApi The session.
   */
  private function getSession($state) {
    $multiPageData = $state->getMultiPageData();
    return $multiPageData['session'];
  }

  /**
   * Check access to the edit page for the specified user id
   * and prepare a user instance for the session participant edit step
   *
   * @param PreRegistrationState $state The pre-registration flow
   * @param SessionApi $session The session in question
   * @param int|null $id The user id
   */
  private function setSessionParticipant($state, $session, $id) {
    // Make sure the session participant can be edited
    if ($id !== NULL) {
      $user = CRUDApiMisc::getById(new UserApi(), $id);

      if ($user === NULL) {
        drupal_set_message('The user you try to edit could not be found!', 'error');

        $this->nextPageName = PreRegistrationPage::SESSION;
        return;
      }
    }
    else {
      $user = new UserApi();
    }

    // Now collect the roles with which we added the participant to a session
    $sessionParticipants = PreRegistrationUtils::getSessionParticipantsAddedByUserForSessionAndUser($state, $session, $user);

    // Did we add the participant to the session with roles or is it a new user?
    if ($user->isUpdate() && (count($sessionParticipants) === 0)) {
      drupal_set_message('You can only edit the users you created or added to a session!', 'error');

      $this->nextPageName = PreRegistrationPage::SESSION;
      return;
    }

    $state->setMultiPageData(array(
      'session' => $session,
      'user' => $user,
      'session_participants' => $sessionParticipants
    ));

    $this->nextPageName = PreRegistrationPage::SESSION_PARTICIPANT;
  }
}
