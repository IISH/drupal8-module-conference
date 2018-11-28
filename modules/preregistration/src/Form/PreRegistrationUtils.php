<?php
namespace Drupal\iish_conference_preregistration\Form;

use Drupal\iish_conference\API\SettingsApi;
use Drupal\iish_conference\API\CRUDApiMisc;
use Drupal\iish_conference\API\ApiCriteriaBuilder;
use Drupal\iish_conference\API\CachedConferenceApi;

use Drupal\iish_conference\API\Domain\UserApi;
use Drupal\iish_conference\API\Domain\PaperApi;
use Drupal\iish_conference\API\Domain\SessionApi;
use Drupal\iish_conference\API\Domain\PaperCoAuthorApi;
use Drupal\iish_conference\API\Domain\ParticipantTypeApi;
use Drupal\iish_conference\API\Domain\ParticipantVolunteeringApi;
use Drupal\iish_conference\API\Domain\CombinedSessionParticipantApi;

/**
 * Contains utilities methods for the pre registration
 */
class PreRegistrationUtils {

  /**
   * Whether to show network during the pre registration
   *
   * @return bool Returns true when networks can be shown
   */
  public static function showNetworks() {
    return SettingsApi::getSetting(SettingsApi::SHOW_NETWORK, 'bool');
  }

  /**
   * Whether the pre registration should use pre-defined sessions rather than newly made sessions by users
   *
   * @return bool Returns true if pre-defined sessions should be used
   */
  public static function useSessions() {
    return SettingsApi::getSetting(SettingsApi::PREREGISTRATION_SESSIONS, 'bool');
  }

  /**
   * Returns whether author registration is still opened
   *
   * @return bool Whether author registration is still opened
   */
  public static function isAuthorRegistrationOpen() {
    return (
      SettingsApi::getSetting(SettingsApi::SHOW_AUTHOR_REGISTRATION, 'bool') &&
      SettingsApi::getSetting(SettingsApi::AUTHOR_REGISTRATION_LASTDATE, 'lastdate')
    );
  }

  /**
   * Returns whether organizer registration is still opened
   *
   * @return bool Whether organizer registration is still opened
   */
  public static function isOrganizerRegistrationOpen() {
    return (
      SettingsApi::getSetting(SettingsApi::SHOW_ORGANIZER_REGISTRATION, 'bool') &&
      SettingsApi::getSetting(SettingsApi::ORGANIZER_REGISTRATION_LASTDATE, 'lastdate')
    );
  }

  /**
   * If networks are not shown, take the given form element, hide it and set the default network id
   *
   * @param array $formElement The form element in question
   */
  public static function hideAndSetDefaultNetwork(array &$formElement) {
    if (!self::showNetworks()) {
      $networkId = SettingsApi::getSetting(SettingsApi::DEFAULT_NETWORK_ID);

      $value = $networkId;
      if (isset($formElement['#multiple']) && ($formElement['#multiple'] === TRUE)) {
        $value = array($networkId => $networkId);
      }

      $formElement['#access'] = FALSE;
      $formElement['#default_value'] = $value;
    }
  }

  /**
   * Returns all participant types with which the user may add him/herself to sessions
   *
   * @return ParticipantTypeApi[] All participant types with which the user may add him/herself to sessions
   */
  public static function getParticipantTypesForUser() {
    $typesToShow = SettingsApi::getSetting(SettingsApi::SHOW_SESSION_PARTICIPANT_TYPES_REGISTRATION, 'list');

    $participantTypes = array();
    foreach (CachedConferenceApi::getParticipantTypes() as $participantType) {
      if (!$participantType->getWithPaper() && (array_search($participantType->getId(), $typesToShow) !== FALSE)) {
        $participantTypes[] = $participantType;
      }
    }

    return $participantTypes;
  }

  /**
   * Returns all papers of the user
   *
   * @param PreRegistrationState $state The state of the pre registration
   *
   * @return PaperApi[] All papers of the user
   */
  public static function getPapersOfUser($state) {
    $user = $state->getUser();

    $props = new ApiCriteriaBuilder();
    return PaperApi::getListWithCriteria(
      $props
        ->eq('user_id', $user->getId())
        ->eq('addedBy_id', $user->getId())
        ->get()
    )->getResults();
  }

  /**
   * Returns all chosen volunteering options of the user
   *
   * @param PreRegistrationState $state The state of the pre registration
   *
   * @return ParticipantVolunteeringApi[] All volunteering choices of the user
   */
  public static function getAllVolunteeringOfUser($state) {
    $volunteering = array();
    $participant = $state->getParticipant();

    if (SettingsApi::getSetting(SettingsApi::SHOW_CHAIR_DISCUSSANT_POOL, 'bool') ||
      SettingsApi::getSetting(SettingsApi::SHOW_LANGUAGE_COACH_PUPIL, 'bool')
    ) {
      $volunteering = CRUDApiMisc::getAllWherePropertyEquals(
        new ParticipantVolunteeringApi(), 'participantDate_id', $participant->getId()
      )->getResults();
    }

    return $volunteering;
  }

  /**
   * Returns all session participants added by the user
   *
   * @param PreRegistrationState $state The state of the pre registration
   *
   * @return CombinedSessionParticipantApi[] All session participants added by the user
   */
  public static function getSessionParticipantsAddedByUser($state) {
    $user = $state->getUser();

    return CRUDApiMisc::getAllWherePropertyEquals(
      new CombinedSessionParticipantApi(), 'addedBy_id', $user->getId()
    )->getResults();
  }

  /**
   * Returns all session participants added by the user for a given session
   *
   * @param PreRegistrationState $state The state of the pre registration
   * @param SessionApi $session The session in question
   *
   * @return CombinedSessionParticipantApi[] All session participants added by the user for the given session
   */
  public static function getSessionParticipantsAddedByUserForSession($state, $session) {
    $user = $state->getUser();

    $props = new ApiCriteriaBuilder();
    return CombinedSessionParticipantApi::getListWithCriteria(
      $props
        ->eq('session_id', $session->getId())
        ->eq('addedBy_id', $user->getId())
        ->get()
    )->getResults();
  }

  /**
   * Returns all session participant information for the user
   * with which he/she added him/herself to sessions with the given type
   *
   * @param PreRegistrationState $state The state of the pre registration
   * @param ParticipantTypeApi $type The type with which the user is added to the sessions
   *
   * @return CombinedSessionParticipantApi[] All session participant information for the user
   * with which he/she added him/herself to sessions with the given type
   */
  public static function getSessionParticipantsOfUserWithType($state, $type) {
    $user = $state->getUser();

    $props = new ApiCriteriaBuilder();
    return CombinedSessionParticipantApi::getListWithCriteria(
      $props
        ->eq('type_id', $type->getId())
        ->eq('user_id', $user->getId())
        ->eq('addedBy_id', $user->getId())
        ->get()
    )->getResults();
  }

  /**
   * Returns all session participants added by the user for a given session and a given user
   *
   * @param PreRegistrationState $state The state of the pre registration
   * @param SessionApi $session The session in question
   * @param UserApi $user The user in question
   *
   * @return CombinedSessionParticipantApi[] All session participants added by the user for the given session and user
   */
  public static function getSessionParticipantsAddedByUserForSessionAndUser($state, $session, $user) {
    $preRegisterUser = $state->getUser();

    $props = new ApiCriteriaBuilder();
    return CombinedSessionParticipantApi::getListWithCriteria(
      $props
        ->eq('session_id', $session->getId())
        ->eq('user_id', $user->getId())
        ->eq('addedBy_id', $preRegisterUser->getId())
        ->get()
    )->getResults();
  }

  /**
   * Returns the session participant information for the user
   * with which he/she added him/herself to the given session with the given type
   *
   * @param PreRegistrationState $state The state of the pre registration
   * @param int $sessionId The session id in question
   * @param int $typeId The type id in question
   *
   * @return CombinedSessionParticipantApi The session participant information for the user
   * with which he/she added him/herself to the given session with the given type
   */
  public static function getSessionParticipantsOfUserWithSessionAndType($state, $sessionId, $typeId) {
    $user = $state->getUser();

    $props = new ApiCriteriaBuilder();
    return CombinedSessionParticipantApi::getListWithCriteria(
      $props
        ->eq('session_id', $sessionId)
        ->eq('type_id', $typeId)
        ->eq('user_id', $user->getId())
        ->eq('addedBy_id', $user->getId())
        ->get()
    )->getFirstResult();
  }

  /**
   * Returns all session participants for a given session and a given user
   *
   * @param PreRegistrationState $state The state of the pre registration
   * @param SessionApi $session The session in question
   * @param UserApi $user The user in question
   *
   * @return CombinedSessionParticipantApi[] All session participants for the given session and user
   */
  public static function getSessionParticipantsNotAddedByUserForSessionAndUser($state, $session, $user) {
    $preRegisterUser = $state->getUser();

    $props = new ApiCriteriaBuilder();
    $sessionParticipants = CombinedSessionParticipantApi::getListWithCriteria(
      $props
        ->eq('session_id', $session->getId())
        ->eq('user_id', $user->getId())
        ->get()
    )->getResults();

    $sessionParticipantsNotAddedByUser = array();
    foreach ($sessionParticipants as $sessionParticipant) {
      if ($sessionParticipant->getAddedById() != $preRegisterUser->getId()) {
        $sessionParticipantsNotAddedByUser[] = $sessionParticipant;
      }
    }

    return $sessionParticipantsNotAddedByUser;
  }

  /**
   * Returns the paper for a given session and user, or a new one if no paper could be found
   *
   * @param PreRegistrationState $state The state of the pre registration
   * @param SessionApi $session The session in question
   * @param UserApi $user The user in question
   *
   * @return PaperApi The paper for a given session and user
   */
  public static function getPaperForSessionAndUser($state, $session, $user) {
    $preRegisterUser = $state->getUser();

    $props = new ApiCriteriaBuilder();
    $paper = PaperApi::getListWithCriteria(
      $props
        ->eq('session_id', $session->getId())
        ->eq('user_id', $user->getId())
        ->eq('addedBy_id', $preRegisterUser->getId())
        ->get()
    )->getFirstResult();

    return ($paper !== NULL) ? $paper : new PaperApi();
  }

  /**
   * Returns the paper co-author for a given session and co-author
   *
   * @param PreRegistrationState $state The state of the pre registration
   * @param SessionApi $session The session in question
   * @param UserApi $user The co-author in question
   *
   * @return PaperCoAuthorApi The paper co-author for a given session and
   *   co-author
   */
  public static function getPaperCoAuthor($state, $session, $user) {
    $preRegisterUser = $state->getUser();

    $props = new ApiCriteriaBuilder();
    $paperCoAuthors = PaperCoAuthorApi::getListWithCriteria(
      $props
        ->eq('user_id', $user->getId())
        ->eq('addedBy_id', $preRegisterUser->getId())
        ->get()
    );

    foreach ($paperCoAuthors->getResults() as $paperCoAuthor) {
      if ($paperCoAuthor->getPaper()->getSessionId() == $session->getId()) {
        return $paperCoAuthor;
      }
    }

    return new PaperCoAuthorApi();
  }

  /**
   * Returns all papers added to the given session not by the user themselves
   *
   * @param PreRegistrationState $state The state of the pre registration
   * @param SessionApi $session The session in question
   * @param UserApi $user The user in question
   *
   * @return PaperApi[] All papers added to the given session
   */
  public static function getPapersOfSession($state, $session, $user) {
    $preRegisterUser = $state->getUser();

    $props = new ApiCriteriaBuilder();
    return PaperApi::getListWithCriteria(
      $props
        ->ne('user_id', $user->getId())
        ->eq('session_id', $session->getId())
        ->eq('addedBy_id', $preRegisterUser->getId())
        ->get()
    )->getResults();
  }
} 
