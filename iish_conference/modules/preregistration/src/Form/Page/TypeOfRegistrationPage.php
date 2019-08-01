<?php
namespace Drupal\iish_conference_preregistration\Form\Page;

use Drupal\Core\Form\FormStateInterface;

use Drupal\iish_conference\EasyProtection;

use Drupal\iish_conference\API\SettingsApi;
use Drupal\iish_conference\API\CRUDApiMisc;
use Drupal\iish_conference\API\CRUDApiClient;
use Drupal\iish_conference\API\CachedConferenceApi;

use Drupal\iish_conference\API\Domain\PaperApi;
use Drupal\iish_conference\API\Domain\SessionApi;
use Drupal\iish_conference\API\Domain\ParticipantTypeApi;
use Drupal\iish_conference\API\Domain\SessionParticipantApi;

use Drupal\iish_conference_preregistration\Form\PreRegistrationState;
use Drupal\iish_conference_preregistration\Form\PreRegistrationUtils;

/**
 * The type of registration page.
 */
class TypeOfRegistrationPage extends PreRegistrationPage {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'iish_conference_preregistration_type_of_registration';
  }

  /**
   * Indicates whether this page is open
   *
   * @return bool Returns true if this page is open
   */
  public function isOpen() {
    $showAuthor = SettingsApi::getSetting(SettingsApi::SHOW_AUTHOR_REGISTRATION);
    $showOrganizer = SettingsApi::getSetting(SettingsApi::SHOW_ORGANIZER_REGISTRATION);
    $typesToShow = SettingsApi::getSetting(SettingsApi::SHOW_SESSION_PARTICIPANT_TYPES_REGISTRATION, 'list');

    return ($showAuthor || $showOrganizer || (count($typesToShow) > 0));
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
    $data = array();


	if (
		(
			!SettingsApi::getSetting(SettingsApi::SHOW_AUTHOR_REGISTRATION, 'bool')
			|| ( SettingsApi::getSetting(SettingsApi::SHOW_AUTHOR_REGISTRATION, 'bool') && !PreRegistrationUtils::isAuthorRegistrationOpen() )
		)
		&&
		(
			!SettingsApi::getSetting(SettingsApi::SHOW_ORGANIZER_REGISTRATION, 'bool')
			|| ( SettingsApi::getSetting(SettingsApi::SHOW_ORGANIZER_REGISTRATION, 'bool') && !PreRegistrationUtils::isOrganizerRegistrationOpen() )
		)
	) {
		// don't show author and organizer part
		//
	} else {
		// show author and/or organizer part

    // + + + + + + + + + + + + + + + + + + + + + + + +
    // AUTHOR

    if (SettingsApi::getSetting(SettingsApi::SHOW_AUTHOR_REGISTRATION, 'bool')) {
      $form['author'] = array(
        '#type' => 'fieldset',
        '#title' => iish_t('I would like to propose a paper'),
      );

      if (PreRegistrationUtils::isAuthorRegistrationOpen()) {
        $papers = PreRegistrationUtils::getPapersOfUser($state);
        $maxPapers = SettingsApi::getSetting(SettingsApi::MAX_PAPERS_PER_PERSON_PER_SESSION);
        $canSubmitNewPaper = (($maxPapers === NULL) || (count($papers) < $maxPapers));
        $data['canSubmitNewPaper'] = $canSubmitNewPaper;

        if ($canSubmitNewPaper) {
          $this->buildNextButton($form['author'], 'typeofregistration_paper', iish_t('Add a new paper'));
          $form['author']['typeofregistration_paper']['suffix']['#markup'] = '<br /><br />';
        }

        $printOr = TRUE;
        foreach ($papers as $paper) {
          if ($printOr && $canSubmitNewPaper) {
            $form['author']['prefix']['#markup'] = ' &nbsp;' . iish_t('or') . '<br /><br />';
            $printOr = FALSE;
          }
          $this->buildNextButton($form['author'], 'typeofregistration_paper_' . $paper->getId(), iish_t('Edit paper'));
          $form['author']['typeofregistration_paper_' . $paper->getId()]['suffix']['#markup'] = ' ' . $paper->getTitle() . '<br /><br />';
        }
      }
      else {
        $form['author']['closed_message'] = array(
          '#markup' =>
            '<span class="eca_warning">' . iish_t('It is no longer possible to pre-register a paper.') . '<br/ >' .
            iish_t('You can still pre-register for the conference as a spectator.') . '</span>',
        );
      }
    }

    // + + + + + + + + + + + + + + + + + + + + + + + +
    // ORGANIZER

    if (SettingsApi::getSetting(SettingsApi::SHOW_ORGANIZER_REGISTRATION, 'bool')) {
      $form['organizer'] = array(
        '#type' => 'fieldset',
        '#title' => iish_t('I\'m an organizer and I would like to propose a session (including multiple participants and papers)'),
      );

      if (PreRegistrationUtils::isOrganizerRegistrationOpen()) {
        if (PreRegistrationUtils::useSessions()) {
          // Use 'session-inline' to trigger css styling on the parent/wrapper div of this select
          $form['organizer']['session-inline'] = array(
            '#type' => 'select',
            '#title' => iish_t('Session'),
            '#options' => CachedConferenceApi::getSessionsKeyValue(),
            '#empty_option' => '- ' . iish_t('Select a session') . ' -',
          );

          $this->buildNextButton($form['organizer'], 'typeofregistration_existing_session', iish_t('Organize session'));
          $form['organizer']['typeofregistration_existing_session']['suffix']['#markup'] = '<br /><br />';
        }
        else {
          $this->buildNextButton($form['organizer'], 'typeofregistration_session', iish_t('Add a new session'));
          $form['organizer']['typeofregistration_session']['suffix']['#markup'] = '<br /><br />';
        }

        $sessionParticipants = PreRegistrationUtils::getSessionParticipantsAddedByUser($state);
        $sessions = SessionParticipantApi::getAllSessions($sessionParticipants);

        $printOr = TRUE;
        foreach (array_unique($sessions) as $session) {
          if ($printOr) {
            $form['organizer']['prefix']['#markup'] = ' &nbsp;' . iish_t('or') . '<br /><br />';
            $printOr = FALSE;
          }
          $this->buildNextButton($form['organizer'], 'typeofregistration_session_' . $session->getId(), iish_t('Edit session'));
          $form['organizer']['typeofregistration_session_' . $session->getId()]['suffix']['#markup'] = ' ' . $session->getName() . '<br /><br />';
        }
      }
      else {
        $form['organizer']['closed_message'] = array(
          '#markup' =>
            '<span class="eca_warning">' . iish_t('It is no longer possible to propose a session.') . '<br/ >' .
            iish_t('You can still pre-register for the conference as a spectator.') . '</span>',
        );
      }
    }

	}

    // + + + + + + + + + + + + + + + + + + + + + + + +
    // SESSION PARTICIPANT TYPES

    $participantTypes = PreRegistrationUtils::getParticipantTypesForUser();
    if (count($participantTypes) > 0) {
      $typesOr = strtolower(implode(' or ', $participantTypes));

      $form['sessionparticipanttypes'] = array(
        '#type' => 'fieldset',
        '#title' => iish_t('I would like to register as a @types in one or multiple sessions',
          array('@types' => $typesOr)),
      );

      if (PreRegistrationUtils::isAuthorRegistrationOpen()) {
        $this->buildNextButton($form['sessionparticipanttypes'], 'typeofregistration_sessionparticipanttypes',
          iish_t('Register as a @types', array('@types' => $typesOr)));
        $form['sessionparticipanttypes']['suffix']['#markup'] = '<br /><br />';

        foreach ($participantTypes as $participantType) {
          $sessionParticipants =
            PreRegistrationUtils::getSessionParticipantsOfUserWithType($state, $participantType);

          if (count($sessionParticipants) > 0) {
            $sessions = CRUDApiClient::getForMethod($sessionParticipants, 'getSession');

            $form['sessionparticipanttypes']['type_' . $participantType->getId()] = array(
              array(
                '#markup' => '<strong>' . iish_t('I would like to be a @type in the sessions',
                    array('@type' => strtolower($participantType))) . ':</strong>'
              ),
              array('#theme' => 'item_list', '#items' => $sessions),
            );
          }
        }
      }
      else {
        $form['sessionparticipanttypes']['closed_message'] = array(
          '#markup' =>
            '<span class="eca_warning">' . iish_t('It is no longer possible to pre-register as @types ' .
              'in one or multiple sessions.', array('@types' => $typesOr)) . '<br/ >' .
            iish_t('You can still pre-register for the conference as a spectator.') . '</span>',
        );
      }
    }

    // + + + + + + + + + + + + + + + + + + + + + + + +
    // SPECTATOR

    $form['spectator'] = array(
      '#type' => 'fieldset',
      '#title' => iish_t('I would like to register as a spectator'),
    );

    $form['spectator']['help_text'] = array(
      '#markup' => iish_t('Then you may skip this page and go right away to the comments and confirmation page.'),
    );

    // + + + + + + + + + + + + + + + + + + + + + + + +

    $commentsPage = new CommentsPage();

    $valueNextPage = iish_t('Next to confirmation page');
    if ($commentsPage->isOpen()) {
      $valueNextPage = iish_t('Next to general comments page');
    }

    $this->buildPrevButton($form, 'typeofregistration_prev', iish_t('Back to personal info'));
    $this->buildNextButton($form, 'typeofregistration_next', $valueNextPage);

    $state->setFormData($data);

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
    $data = $state->getFormData();
    $submitName = $form_state->getTriggeringElement()['#name'];

    if ($submitName === 'typeofregistration_next') {
      $commentsPage = new CommentsPage();

      if ($commentsPage->isOpen()) {
        $this->nextPageName = PreRegistrationPage::COMMENTS;
        return;
      }

      $this->nextPageName = PreRegistrationPage::CONFIRM;
      return;
    }

    if (PreRegistrationUtils::isAuthorRegistrationOpen()) {
      if (($submitName === 'typeofregistration_paper') && $data['canSubmitNewPaper']) {
        $this->setPaper($state, NULL);
        return;
      }

      if (strpos($submitName, 'typeofregistration_paper_') === 0) {
        $id = EasyProtection::easyIntegerProtection(str_replace('typeofregistration_paper_', '', $submitName));

        $this->setPaper($state, $id);
        return;
      }
    }

    if (PreRegistrationUtils::isOrganizerRegistrationOpen()) {
      if ($submitName === 'typeofregistration_session') {
        $this->setSession($state, NULL);
        return;
      }

      if (strpos($submitName, 'typeofregistration_session_') === 0) {
        $id = EasyProtection::easyIntegerProtection(str_replace('typeofregistration_session_', '', $submitName));

        $this->setSession($state, $id);
        return;
      }

      if ($submitName === 'typeofregistration_existing_session') {
        $id = EasyProtection::easyIntegerProtection($form_state->getValue('session-inline'));

        $this->setSession($state, $id, TRUE);
        return;
      }
    }

    if (PreRegistrationUtils::isAuthorRegistrationOpen() && ($submitName === 'typeofregistration_sessionparticipanttypes')) {
      $this->nextPageName = PreRegistrationPage::SESSION_PARTICIPANT_TYPES;
      return;
    }

    $this->nextPageName = PreRegistrationPage::TYPE_OF_REGISTRATION;
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
    $this->nextPageName = PreRegistrationPage::PERSONAL_INFO;
  }

  /**
   * Check access to the edit page for the specified paper id and prepare a paper instance for the paper edit step
   *
   * @param PreRegistrationState $state The pre-registration flow
   * @param int|null $id The paper id
   */
  private function setPaper($state, $id) {
    $user = $state->getUser();

    // Make sure the paper can be edited
    if ($id !== NULL) {
      $paper = CRUDApiMisc::getById(new PaperApi(), $id);

      if ($paper === NULL) {
        drupal_set_message('The paper you try to edit could not be found!', 'error');
        $this->nextPageName = PreRegistrationPage::PERSONAL_INFO;
        return;
      }

      if (($paper->getAddedById() != $user->getId()) || ($paper->getUserId() != $user->getId())) {
        drupal_set_message('You can only edit the papers you created!', 'error');
        $this->nextPageName = PreRegistrationPage::PERSONAL_INFO;
        return;
      }
    }
    else {
      $paper = new PaperApi();
    }

    $state->setMultiPageData(array('paper' => $paper));
    $this->nextPageName = PreRegistrationPage::PAPER;
  }

  /**
   * Check access to the edit page for the specified session id and prepare a session instance for the session edit step
   *
   * @param PreRegistrationState $state The pre-registration flow
   * @param int|null $id The session id
   * @param bool $addAsOrganizer Whether to add the user as organizer to the session right away
   */
  private function setSession($state, $id, $addAsOrganizer = FALSE) {
    $user = $state->getUser();

    // Make sure the session can be edited
    if ($id !== NULL) {
      $session = CRUDApiMisc::getById(new SessionApi(), $id);

      if ($session === NULL) {
        drupal_set_message('The session you try to edit could not be found!', 'error');
        $this->nextPageName = PreRegistrationPage::TYPE_OF_REGISTRATION;
        return;
      }

      if (!PreRegistrationUtils::useSessions() && ($session->getAddedById() != $user->getId())) {
        drupal_set_message('You can only edit the sessions you created!', 'error');
        $this->nextPageName = PreRegistrationPage::TYPE_OF_REGISTRATION;
        return;
      }
    }
    else {
      if (PreRegistrationUtils::useSessions()) {
        drupal_set_message('Please select the session you would like to organize!', 'error');
        $this->nextPageName = PreRegistrationPage::TYPE_OF_REGISTRATION;
        return;
      }
      else {
        $session = new SessionApi();
      }
    }

    if (PreRegistrationUtils::useSessions() && $addAsOrganizer) {
      $organiser = new SessionParticipantApi();
      $organiser->setUser($user);
      $organiser->setSession($session);
      $organiser->setType(ParticipantTypeApi::ORGANIZER_ID);

      $organiser->save();
      drupal_set_message(iish_t('You are added as organizer to this session. ' .
        'Please add participants to the session.'), 'status');
    }

    $state->setMultiPageData(array('session' => $session));
    $this->nextPageName = PreRegistrationPage::SESSION;
  }
}
