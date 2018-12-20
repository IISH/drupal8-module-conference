<?php
namespace Drupal\iish_conference\API;

use Drupal\iish_conference\API\Domain\UserApi;
use Drupal\iish_conference\API\Domain\ParticipantDateApi;
use Drupal\iish_conference\API\Domain\ParticipantStateApi;

/**
 * Holds information about the currently logged in user
 */
class LoggedInUserDetails {
  const USER_STATUS_DOES_NOT_EXISTS = 0;
  const USER_STATUS_EXISTS = 1;
  const USER_STATUS_DISABLED = 2;
  const USER_STATUS_DELETED = 3;
  const USER_STATUS_EMAIL_DISCONTINUED = 4;
  const USER_STATUS_PARTICIPANT_CANCELLED = 5;
  const USER_STATUS_PARTICIPANT_DOUBLE_ENTRY = 6;

  private static $user = NULL;
  private static $participant = NULL;

  /**
   * Is the user currently logged in?
   *
   * @return bool Whether the user is currently logged in
   */
  public static function isLoggedIn() {
    $loggedIn = is_int(self::getId());
    if (!$loggedIn) {
      self::autoLogin();
      $loggedIn = is_int(self::getId());
    }
    return $loggedIn;
  }

  /**
   * Returns the user id of the currently logged in user, if logged in
   *
   * @return int|null The user id
   */
  public static function getId() {
    $id = NULL;
    $storedId = self::getFromSession('user_id');
    if (is_int($storedId) && ($storedId > 0)) {
      $id = $storedId;
    }

    return $id;
  }

  /**
   * Returns the email address of the currently logged in user, if logged in
   *
   * @return string|null The email address
   */
  public static function getEmail() {
    return self::getFromSession('user_email');
  }

  /**
   * Is the user currently logged in a participant?
   *
   * @return bool Whether the logged in user is a participant
   */
  public static function isAParticipant() {
    return (
      (self::getParticipant() !== NULL) &&
      (self::getParticipant()->getStateId() !== ParticipantStateApi::DID_NOT_FINISH_REGISTRATION)
    );
  }

  /**
   * Is the user currently logged in a participant who did not finish his/her registration yet?
   *
   * @return bool hether the logged in user is a participant who did not finish his/her registration yet
   */
  public static function isAParticipantWithoutConfirmation() {
    return (
      (self::getParticipant() !== NULL) &&
      (self::getParticipant()->getStateId() == ParticipantStateApi::DID_NOT_FINISH_REGISTRATION)
    );
  }

  /**
   * Returns the user details of the currently logged in user, if logged in
   *
   * @return UserApi|null The user details
   */
  public static function getUser() {
    if (self::$user !== NULL) {
      return self::$user;
    }

    $userId = self::getId();
    $user = self::getFromSession('user');

    if ($user !== NULL) {
      $user = unserialize($user);
    }
    else {
      if (($user === NULL) && is_int($userId)) {
        $user = CRUDApiMisc::getById(new UserApi(), $userId);
        self::setUser($user);
      }
    }
    self::$user = $user;

    return $user;
  }

  /**
   * Returns the participant details of the currently logged in user, if logged in and a participant
   *
   * @return ParticipantDateApi|null The participant details
   */
  public static function getParticipant() {
    if (self::$participant !== NULL) {
      return self::$participant;
    }

    $userId = self::getId();
    $participant = self::getFromSession('participant');

    if ($participant !== NULL) {
      $participant = unserialize($participant);
    }
    else {
      if (($participant === NULL) && is_int($userId)) {
        $participant = CRUDApiMisc::getFirstWherePropertyEquals(new ParticipantDateApi(), 'user_id', $userId);
        self::setParticipant($participant);
      }
    }
    self::$participant = $participant;

    return $participant;
  }

  /**
   * Does the logged in user have full rights?
   *
   * @return bool Whether the logged in user has full rights
   */
  public static function hasFullRights() {
    return (self::getFromSession('hasFullRights') === TRUE);
  }

  /**
   * Is the logged in user a network chair?
   *
   * @return bool Whether the logged in user is a network chair
   */
  public static function isNetworkChair() {
    return (self::getFromSession('isNetworkChair') === TRUE);
  }

  /**
   * Is the logged in user a session chair?
   *
   * @return bool Whether the logged in user is a session chair
   */
  public static function isChair() {
    return (self::getFromSession('isChair') === TRUE);
  }

  /**
   * Is the logged in user a session organiser?
   *
   * @return bool Whether the logged in user is a session organiser
   */
  public static function isOrganiser() {
    return (self::getFromSession('isOrganiser') === TRUE);
  }

  /**
   * Is the logged in user a crew member?
   *
   * @return bool Whether the logged in user is a crew member
   */
  public static function isCrew() {
    return (self::getFromSession('isCrew') === TRUE);
  }

  /**
   * Invalidates the cached user instance
   */
  public static function invalidateUser() {
    if (isset($_SESSION['iish_conference']['user'])) {
      unset($_SESSION['iish_conference']['user']);
    }
  }

  /**
   * Invalidates the cached participant instance
   */
  public static function invalidateParticipant() {
    if (isset($_SESSION['iish_conference']['participant'])) {
      unset($_SESSION['iish_conference']['participant']);
    }
  }

  /**
   * Set the currently logged in user without logging in
   *
   * @param int|UserApi $user The (id of the) user in question
   * @param bool $hasFullRights Whether the user will have full rights
   * @param bool $isNetworkChair Whether the user will be a network chair
   * @param bool $isChair Whether the user will be a chair
   * @param bool $isOrganiser Whether the user will be an organiser
   * @param bool $isCrew Whether the user will be a crew member
   *
   * @return int The user status of the currently logged in user
   */
  public static function setCurrentlyLoggedIn($user, $hasFullRights = FALSE, $isNetworkChair = FALSE,
                                              $isChair = FALSE, $isOrganiser = FALSE, $isCrew = FALSE) {
    if (!($user instanceof UserApi)) {
      $user = CRUDApiMisc::getById(new UserApi(), $user);
    }
    $participant = CRUDApiMisc::getFirstWherePropertyEquals(new ParticipantDateApi(), 'user_id', $user->getId());

    return self::setCurrentlyLoggedInWithResponse(array(
      'status' => self::USER_STATUS_EXISTS,
      'hasFullRights' => $hasFullRights,
      'isNetworkChair' => $isNetworkChair,
      'isChair' => $isChair,
      'isOrganiser' => $isOrganiser,
      'isCrew' => $isCrew,
      'user' => $user,
      'participant' => $participant,
    ));
  }

  /**
   * Set the currently logged in user for use with the LoginApi or the UserInfoApi
   *
   * @param array $response The response from either api calls
   *
   * @return int The user status of the currently logged in user
   */
  public static function setCurrentlyLoggedInWithResponse(array $response) {
    $userStatus = self::USER_STATUS_DOES_NOT_EXISTS;
    $_SESSION['iish_conference']['user_email'] = NULL;
    $_SESSION['iish_conference']['user_id'] = NULL;

    if ($response !== NULL) {
      $userStatus = $response['status'];
      if ($userStatus == LoggedInUserDetails::USER_STATUS_EXISTS) {
        $_SESSION['iish_conference']['hasFullRights'] = $response['hasFullRights'];
        $_SESSION['iish_conference']['isNetworkChair'] = $response['isNetworkChair'];
        $_SESSION['iish_conference']['isChair'] = $response['isChair'];
        $_SESSION['iish_conference']['isOrganiser'] = $response['isOrganiser'];
        $_SESSION['iish_conference']['isCrew'] = $response['isCrew'];

        $user = NULL;
        if ($response['user'] instanceof UserApi) {
          $user = $response['user'];
        }
        else {
          if (is_array($response['user'])) {
            $user = UserApi::getUserFromArray($response['user']);
          }
        }
        self::setUser($user);

        $participant = NULL;
        if ($response['participant'] instanceof ParticipantDateApi) {
          $participant = $response['participant'];
        }
        else {
          if (is_array($response['participant'])) {
            $participant = ParticipantDateApi::getParticipantDateFromArray($response['participant']);
          }
        }
        self::setParticipant($participant);

        $_SESSION['iish_conference']['created_at'] = time();
      }
    }

    return $userStatus;
  }

  /**
   * Invalidates the session.
   */
  public static function invalidateSession() {
    $_SESSION['iish_conference']['user_id'] = NULL;
    $_SESSION['iish_conference']['user_email'] = NULL;

    $_SESSION['iish_conference']['login_default_email_existingusers'] = NULL;
    $_SESSION['iish_conference']['login_default_email_newusers'] = NULL;

    $_SESSION['iish_conference']['hasFullRights'] = NULL;
    $_SESSION['iish_conference']['isNetworkChair'] = NULL;
    $_SESSION['iish_conference']['isChair'] = NULL;
    $_SESSION['iish_conference']['isOrganiser'] = NULL;
    $_SESSION['iish_conference']['isCrew'] = NULL;

    $_SESSION['iish_conference']['user'] = NULL;
    $_SESSION['iish_conference']['participant'] = NULL;

    unset($_SESSION['storage']);
  }

  /**
   * Caches the user instance in the users session
   *
   * @param UserApi $user The user in question
   */
  private static function setUser($user) {
    if ($user !== NULL) {
      $_SESSION['iish_conference']['user_email'] = $user->getEmail();
      $_SESSION['iish_conference']['user_id'] = $user->getId();
      $_SESSION['iish_conference']['user'] = serialize($user);
    }
  }

  /**
   * Caches the participant instance in the users session
   *
   * @param ParticipantDateApi $participant The participant in question
   */
  private static function setParticipant($participant) {
    if ($participant !== NULL) {
      $_SESSION['iish_conference']['participant'] = serialize($participant);
    }
  }

  /**
   * Loads a property concerning the logged in user from the session.
   * If the data stored in the session is expired, the data is automatically refreshed
   *
   * @param string $property The name of the property in question
   *
   * @return mixed The stored value for the given property, or false in case it does not exist.
   */
  private static function getFromSession($property) {
    $expiresIn = 60 * 60 * 24; // Expires in one day time
    if (isset($_SESSION['iish_conference']['created_at'])) {
      $time = $_SESSION['iish_conference']['created_at'];
      if (((($time + $expiresIn) - time()) > 0) && isset($_SESSION['iish_conference'][$property])) {
        return $_SESSION['iish_conference'][$property];
      }
    }

    if (isset($_SESSION['iish_conference']['user_id'])) {
      $userInfo = self::loadUserInfoFromAPI($_SESSION['iish_conference']['user_id']);
      self::setCurrentlyLoggedInWithResponse($userInfo);

      if (isset($_SESSION['iish_conference'][$property])) {
        return $_SESSION['iish_conference'][$property];
      }
    }

    return NULL;
  }

  /**
   * Loads user info from the Conference Management System API
   *
   * @param int $userId The id of the user in question
   *
   * @return array|null User information or null if unsuccessful
   */
  private static function loadUserInfoFromAPI($userId) {
    $userInfoApi = new UserInfoApi();

    return $userInfoApi->userInfo($userId);
  }

  /**
   * Attempt an auto login
   *
   * @return int The user status of the currently logged in user
   */
  private static function autoLogin() {
    if (isset($_GET['id']) && isset($_GET['ulogin'])) {
      $autoLoginApi = new AutoLoginApi();

      return $autoLoginApi->login($_GET['id'], $_GET['ulogin']);
    }

    return NULL;
  }
}