<?php
namespace Drupal\iish_conference_preregistration\Form\Page;

use Drupal\Core\Form\FormStateInterface;

use Drupal\iish_conference\Markup\ConferenceHTML;

use Drupal\iish_conference\API\CRUDApiMisc;
use Drupal\iish_conference\API\SettingsApi;
use Drupal\iish_conference\API\CRUDApiClient;
use Drupal\iish_conference\API\CachedConferenceApi;

use Drupal\iish_conference\API\Domain\UserApi;
use Drupal\iish_conference\API\Domain\CountryApi;
use Drupal\iish_conference\API\Domain\ParticipantDateApi;
use Drupal\iish_conference\API\Domain\ParticipantTypeApi;
use Drupal\iish_conference\API\Domain\SessionParticipantApi;
use Drupal\iish_conference\API\Domain\CombinedSessionParticipantApi;

use Drupal\iish_conference_preregistration\Form\PreRegistrationState;
use Drupal\iish_conference_preregistration\Form\PreRegistrationUtils;

/**
 * The session participant page.
 */
class SessionParticipantPage extends PreRegistrationPage {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'iish_conference_preregistration_session_participant';
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
    $preRegisterUser = $state->getUser();

    $multiPageData = $state->getMultiPageData();
    $session = $multiPageData['session'];
    $user = $multiPageData['user'];
    $sessionParticipants = $multiPageData['session_participants'];

    $participant = CRUDApiMisc::getFirstWherePropertyEquals(new ParticipantDateApi(), 'user_id', $user->getId());
    $participant = ($participant === NULL) ? new ParticipantDateApi() : $participant;

    $paper = PreRegistrationUtils::getPaperForSessionAndUser($state, $session, $user);
    $paperCoAuthor = PreRegistrationUtils::getPaperCoAuthor($state, $session, $user);

    $state->setFormData(array(
      'session' => $session,
      'user' => $user,
      'participant' => $participant,
      'paper' => $paper,
      'paper_co_author' => $paperCoAuthor,
      'session_participants' => $sessionParticipants
    ));

    // + + + + + + + + + + + + + + + + + + + + + + + +
    // PARTICIPANT

    // If the user was added by the currently logged in user, he/she may change him/her
    $readOnlyUser = array();
    $readOnlyParticipant = array();
    if ($user->isUpdate() && ($user->getAddedById() != $preRegisterUser->getId()) &&
      ($user->getId() != $preRegisterUser->getId())
    ) {
      $readOnlyUser['readonly'] = 'readonly';
      $readOnlyUser['class'] = array('readonly-text');
    }
    if ($participant->isUpdate() && ($participant->getAddedById() != $preRegisterUser->getId()) &&
      ($participant->getUserId() != $preRegisterUser->getId())
    ) {
      $readOnlyParticipant['readonly'] = 'readonly';
      $readOnlyParticipant['class'] = array('readonly-text');
    }

    $form['participant'] = array(
      '#type' => 'fieldset',
      '#title' => iish_t('Add a participant'),
    );

    $form['participant']['addparticipantemail'] = array(
      '#type' => 'textfield',
      '#title' => iish_t('E-mail'),
      '#required' => TRUE,
      '#size' => 40,
      '#maxlength' => 100,
      '#default_value' => $user->getEmail(),
      '#attributes' => $readOnlyUser,
    );

    $form['participant']['addparticipantfirstname'] = array(
      '#type' => 'textfield',
      '#title' => iish_t('First name'),
      '#required' => TRUE,
      '#size' => 40,
      '#maxlength' => 255,
      '#default_value' => $user->getFirstName(),
      '#attributes' => $readOnlyUser,
    );

    $form['participant']['addparticipantlastname'] = array(
      '#type' => 'textfield',
      '#title' => iish_t('Last name'),
      '#required' => TRUE,
      '#size' => 40,
      '#maxlength' => 255,
      '#default_value' => $user->getLastName(),
      '#attributes' => $readOnlyUser,
    );

    $form['participant']['addparticipantorganisation'] = array(
      '#type' => 'textfield',
      '#title' => iish_t('Organisation'),
      '#size' => 40,
      '#maxlength' => 255,
      '#required' => TRUE,
      '#default_value' => $user->getOrganisation(),
      '#attributes' => $readOnlyUser,
    );

    if (SettingsApi::getSetting(SettingsApi::SHOW_STUDENT, 'bool')) {
      $form['participant']['addparticipantstudent'] = array(
        '#type' => 'checkbox',
        '#title' => iish_t('Please check if this participant is a (PhD) student'),
        '#default_value' => $participant->getStudent(),
        '#attributes' => $readOnlyParticipant,
      );
    }

    // If a field is required, but turns out to be missing in the existing record, allow the user to add a value
    $userIsReadOnly = isset($readOnlyUser['readonly']);
    $cvRequired = (SettingsApi::getSetting(SettingsApi::REQUIRED_CV, 'bool'));
    $userCv = $user->getCv();
    if (SettingsApi::getSetting(SettingsApi::SHOW_CV, 'bool')) {
      $form['participant']['addparticipantcv'] = array(
        '#type' => 'textarea',
        '#title' => iish_t('Curriculum Vitae'),
        '#description' => '<em>' . iish_t('(optional, max. 200 words)') . '</em>',
        '#rows' => 2,
        '#required' => $cvRequired,
        '#default_value' => $userCv,
        '#attributes' => ($cvRequired && $userIsReadOnly && empty($userCv)) ? array() : $readOnlyUser,
      );
    }

    $userCountryId = ($user->getCountryId() !== null)
      ? $user->getCountryId()
      : CountryApi::getCountryOfEvent()->getId();

    $form['participant']['addparticipantcountry'] = array(
      '#type' => 'select',
      '#title' => iish_t('Country'),
      '#options' => CRUDApiClient::getAsKeyValueArray(CachedConferenceApi::getCountries()),
      '#required' => TRUE,
      '#default_value' => $userCountryId,
      '#attributes' => ($userIsReadOnly && empty($userCountryId)) ? array() : $readOnlyUser,
    );

    // + + + + + + + + + + + + + + + + + + + + + + + +
    // PARTICIPANT ROLES

    $participantTypes = CachedConferenceApi::getParticipantTypes();
    $participantTypeOptions = CRUDApiClient::getAsKeyValueArray($participantTypes);

    $chosenTypes =
      SessionParticipantApi::getAllTypesOfUserForSession($sessionParticipants, $user->getId(), $session->getId());
    $chosenTypeValues = CRUDApiClient::getIds($chosenTypes);

    $description = ParticipantTypeApi::getCombinationsNotAllowedText();
    if (strlen(trim($description)) > 0) {
      $description = new ConferenceHTML($description);
    }
    else {
      $description = '';
    }

    $form['addparticipanttype'] = array(
      '#title' => iish_t('The roles of the participant in this session'),
      '#type' => 'checkboxes',
      '#description' => $description,
      '#required' => TRUE,
      '#options' => $participantTypeOptions,
      '#default_value' => $chosenTypeValues,
    );

    // + + + + + + + + + + + + + + + + + + + + + + + +
    // PARTICIPANT PAPER

    // For which selected participant types should a paper be added as well?
    $visibleStates = array();
    foreach ($participantTypes as $type) {
      if ($type->getWithPaper()) {
        $visibleStates[] =
          array(':input[name="addparticipanttype[' . $type->getId() . ']"]' => array('checked' => TRUE));
        $visibleStates[] = 'or';
      }
    }
    array_pop($visibleStates); // Removes the last 'or'

    $form['participant_paper'] = array(
      '#type' => 'fieldset',
      '#title' => iish_t('Add paper for participant'),
      '#states' => array('visible' => $visibleStates),
    );

    $form['participant_paper']['addparticipantpapertitle'] = array(
      '#type' => 'textfield',
      '#title' => iish_t('Paper title'),
      '#size' => 40,
      '#maxlength' => 255,
      '#default_value' => $paper->getTitle(),
    );

    $form['participant_paper']['addparticipantpaperabstract'] = array(
      '#type' => 'textarea',
      '#title' => iish_t('Paper abstract'),
      '#description' => '<em>' . iish_t('(max. 500 words)') . '</em>',
      '#rows' => 3,
      '#default_value' => $paper->getAbstr(),
    );

    // + + + + + + + + + + + + + + + + + + + + + + + +
    // PARTICIPANT PAPER CO-AUTHOR

    $form['participant_paper_coauthor'] = array(
      '#type'   => 'fieldset',
      '#title'  => iish_t('Select paper of co-author'),
      '#states' => array('visible' => array(
        ':input[name="addparticipanttype[' . ParticipantTypeApi::CO_AUTHOR_ID . ']"]' => array('checked' => TRUE))
      ),
    );

    $form['participant_paper_coauthor']['addpapercoauthor'] = array(
      '#type'          => 'select',
      '#title'         => iish_t('Paper'),
      '#description'   => iish_t('First add the authors and their papers to the session. Then add the co-authors to the session.'),
      '#options'       => CRUDApiClient::getAsKeyValueArray(PreRegistrationUtils::getPapersOfSession($state, $session, $user)),
      '#empty_option'  => '- ' . iish_t('Select a paper') . ' -',
      '#default_value' => $paperCoAuthor->getPaperId(),
    );

    // + + + + + + + + + + + + + + + + + + + + + + + +

    $this->buildPrevButton($form, 'sessionparticipant_back', iish_t('Back'));
    $this->buildNextButton($form, 'sessionparticipant_next', iish_t('Save participant'));

    // We can only remove a participant from a session if he/she has already been added to session
    if (isset($sessionParticipants[0])) {
      $this->buildRemoveButton($form, 'sessionparticipant_remove', iish_t('Remove participant from session'),
        iish_t('Are you sure you want to remove this participant? ' .
          '(The participant will only be removed from this session).'));
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
    $state = new PreRegistrationState($form_state);

    $multiPageData = $state->getMultiPageData();
    $session = $multiPageData['session'];

    $email = trim($form_state->getValue('addparticipantemail'));

    if (!\Drupal::service('email.validator')->isValid($email)) {
      $form_state->setErrorByName('addparticipantemail', iish_t('The e-mail address appears to be invalid.'));
    }

    if (!ParticipantTypeApi::isCombinationOfTypesAllowed($form_state->getValue('addparticipanttype'))) {
      $form_state->setErrorByName('addparticipanttype',
        new ConferenceHTML(ParticipantTypeApi::getCombinationsNotAllowedText()));
    }

    if (ParticipantTypeApi::containsTypeWithPaper($form_state->getValue('addparticipanttype'))) {
      if (strlen(trim($form_state->getValue('addparticipantpapertitle'))) === 0) {
        $form_state->setErrorByName('addparticipantpapertitle', iish_t('Paper title is required with the selected type(s).'));
      }
      if (strlen(trim($form_state->getValue('addparticipantpaperabstract'))) === 0) {
        $form_state->setErrorByName('addparticipantpaperabstract',
          iish_t('Paper abstract is required with the selected type(s).'));
      }
    }

    if (array_search(ParticipantTypeApi::CO_AUTHOR_ID, $form_state->getValue('addparticipanttype')) !== false) {
      if (strlen(trim($form_state->getValue('addpapercoauthor'))) === 0) {
        $form_state->setErrorByName('addpapercoauthor', iish_t('A paper is required for the co-author.'));
      }
    }

    if (PreRegistrationUtils::useSessions()) {
      $email = strtolower(trim($form_state->getValue('addparticipantemail')));
      $foundUser = CRUDApiMisc::getFirstWherePropertyEquals(new UserApi(), 'email', $email);

      if ($foundUser !== NULL) {
        $sessionParticipants = PreRegistrationUtils::getSessionParticipantsNotAddedByUserForSessionAndUser(
          $state, $session, $foundUser
        );

        if (count($sessionParticipants) > 0) {
          $form_state->setErrorByName('addparticipantemail',
            iish_t('This participant has already been added to this session by another organizer.'));
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
    $preRegisterUser = $state->getUser();

    $data = $state->getFormData();
    $session = $data['session'];
    $user = $data['user'];
    $participant = $data['participant'];
    $paper = $data['paper'];
    $paperCoAuthor = $data['paper_co_author'];
    $allToDelete = CombinedSessionParticipantApi::getAllSessionParticipants($data['session_participants']);

    // First check if the user with the given email does not exists already
    $email = strtolower(trim($form_state->getValue('addparticipantemail')));
    $foundUser = CRUDApiMisc::getFirstWherePropertyEquals(new UserApi(), 'email', $email);
    if ($foundUser !== NULL) {
      $user = $foundUser;
      $participant = CRUDApiMisc::getFirstWherePropertyEquals(
        new ParticipantDateApi(), 'user_id', $foundUser->getId()
      );
      $participant = ($participant !== NULL) ? $participant : new ParticipantDateApi();
    }

    $userCv = $user->getCv();
    $userCountry = $user->getCountryId();
    $cvRequired = (SettingsApi::getSetting(SettingsApi::SHOW_CV, 'bool') &&
      SettingsApi::getSetting(SettingsApi::REQUIRED_CV, 'bool') && empty($userCv));
    $countryRequired = empty($userCountry);

    // Then we save the user
    if (!$user->isUpdate() || ($user->getAddedById() == $preRegisterUser->getId()) ||
      ($user->getId() == $preRegisterUser->getId())
    ) {
      $user->setEmail($form_state->getValue('addparticipantemail'));
      $user->setFirstName($form_state->getValue('addparticipantfirstname'));
      $user->setLastName($form_state->getValue('addparticipantlastname'));
      $user->setOrganisation($form_state->getValue('addparticipantorganisation'));
      $user->setCountry($form_state->getValue('addparticipantcountry'));

      if (SettingsApi::getSetting(SettingsApi::SHOW_CV, 'bool')) {
        $user->setCv($form_state->getValue('addparticipantcv'));
      }

      $user->save();
    }
    // If a field is required, but turns out to be missing in the existing record, allow the user to add a value
    else {
      if ($cvRequired || $countryRequired) {
        if ($cvRequired) {
          $user->setCv($form_state->getValue('addparticipantcv'));
        }
        if ($countryRequired) {
          $user->setCountry($form_state->getValue('addparticipantcountry'));
        }

        $user->save();
      }
    }

    // Then save the participant
    if (!$participant->isUpdate() || ($participant->getAddedById() == $preRegisterUser->getId()) |
      ($participant->getUserId() == $preRegisterUser->getId())
    ) {
      if (SettingsApi::getSetting(SettingsApi::SHOW_STUDENT, 'bool')) {
        $participant->setStudent($form_state->getValue('addparticipantstudent'));
      }
      $participant->setUser($user);

      $participant->save();
    }

    // Then save the paper
    if (ParticipantTypeApi::containsTypeWithPaper($form_state->getValue('addparticipanttype'))) {
      $paper->setUser($user);
      $paper->setSession($session);
      $paper->setTitle($form_state->getValue('addparticipantpapertitle'));
      $paper->setAbstr($form_state->getValue('addparticipantpaperabstract'));
      $paper->setAddedBy($preRegisterUser);

      $paper->save();
    }
    else {
      $paper->delete();
    }

    // Then save the paper co-author
    if (array_search(ParticipantTypeApi::CO_AUTHOR_ID, $form_state->getValue('addparticipanttype')) !== false) {
      $paperCoAuthor->setUser($user);
      $paperCoAuthor->setPaper($form_state->getValue('addpapercoauthor'));
      $paperCoAuthor->setAddedBy($preRegisterUser);
      $paperCoAuthor->save();
    }
    else {
      $paperCoAuthor->delete();
    }

    // Last the types
    foreach ($form_state->getValue('addparticipanttype') as $typeId => $type) {
      if ($typeId == $type) {
        $foundInstance = FALSE;
        foreach ($allToDelete as $key => $instance) {
          if ($instance->getTypeId() == $typeId) {
            $foundInstance = TRUE;
            unset($allToDelete[$key]);
            break;
          }
        }

        if (!$foundInstance && ($typeId !== ParticipantTypeApi::CO_AUTHOR_ID)) {
          $sessionParticipant = new SessionParticipantApi();
          $sessionParticipant->setSession($session);
          $sessionParticipant->setUser($user);
          $sessionParticipant->setType($typeId);
          $sessionParticipant->setAddedBy($preRegisterUser);
          $sessionParticipant->save();
        }
      }
    }

    foreach ($allToDelete as $instance) {
      $instance->delete();
    }

    // Now go back to the session form
    $state->setMultiPageData(array('session' => $session));

    $this->nextPageName = PreRegistrationPage::SESSION;
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
    $data = $state->getFormData();

    $session = $data['session'];
    $state->setMultiPageData(array('session' => $session));

    $this->nextPageName = PreRegistrationPage::SESSION;
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
    $state = new PreRegistrationState($form_state);
    $data = $state->getFormData();

    $session = $data['session'];
    $sessionParticipants = CombinedSessionParticipantApi::getAllSessionParticipants($data['session_participants']);

    foreach ($sessionParticipants as $sessionParticipant) {
      $sessionParticipant->delete();
    }

    // Now go back to the session page
    $state->setMultiPageData(array('session' => $session));

    $this->nextPageName = PreRegistrationPage::SESSION;
  }
}
