<?php
namespace Drupal\iish_conference\API\Domain;

use Drupal\iish_conference\API\SettingsApi;
use Drupal\iish_conference\API\CRUDApiMisc;
use Drupal\iish_conference\API\CRUDApiClient;
use Drupal\iish_conference\API\CachedConferenceApi;

/**
 * Holds a session obtained from the API
 */
class SessionApi extends CRUDApiClient {
  protected $name;
  protected $abstr;
  protected $extraInfo;
  protected $state_id;
  protected $type_id;
  protected $differentType;
  protected $papers_id;
  protected $networks_id;
  protected $addedBy_id;

  private $sessionState;
  private $sessionType;
  private $networks;
  private $addedBy;
  private $sessionParticipants;

  /**
   * Creates a new Session.
   * @param bool $new Whether this really concerns a new Session.
   */
  public function __construct($new = TRUE) {
    if ($new) {
      $this->setState(SessionStateApi::NEW_SESSION);
    }
  }

  /**
   * Allows the creation of a session via an array with details
   *
   * @param array $session An array with session details
   *
   * @return SessionApi A session object
   */
  public static function getSessionFromArray(array $session) {
    return self::createNewInstance($session);
  }

  /**
   * Returns all the planned days of the listed sessions
   *
   * @param SessionApi[] $sessions The sessions in question
   *
   * @return DayApi[] The planned days
   */
  public static function getAllPlannedDaysForSessions($sessions) {
    $daysPlanned = array();
    foreach ($sessions as $session) {
      $sessionRoomDateTime = CRUDApiMisc::getFirstWherePropertyEquals(new SessionRoomDateTimeApi(), 'session_id',
        $session->getId());
      if ($sessionRoomDateTime !== NULL) {
        $daysPlanned[] = $sessionRoomDateTime->getDay();
      }
    }
    sort($daysPlanned);

    return array_unique($daysPlanned);
  }

  /**
   * Returns the session name of the current conference
   *
   * @param bool $singular Whether the singular or plural form should be returned
   * @param bool $lowercase Whether it should be all lowercase
   *
   * @return string The session name
   */
  public static function getSessionName($singular = TRUE, $lowercase = FALSE) {
    if ($singular) {
      $sessionName = SettingsApi::getSetting(SettingsApi::SESSION_NAME_SINGULAR);
    }
    else {
      $sessionName = SettingsApi::getSetting(SettingsApi::SESSION_NAME_PLURAL);
    }

    if ($lowercase) {
      $sessionName = strtolower($sessionName);
    }

    return $sessionName;
  }

  /**
   * Returns the abstract of this session
   *
   * @return string The abstract of this session
   */
  public function getAbstr() {
    return $this->abstr;
  }

  /**
   * Set the abstract for this paper
   *
   * @param string $abstr The abstract
   */
  public function setAbstr($abstr) {
    $abstr = (($abstr !== NULL) && strlen(trim($abstr)) > 0) ? trim($abstr) : NULL;

    $this->abstr = $abstr;
    $this->toSave['abstr'] = $abstr;
  }

  /**
   * Returns the extra info of this session
   *
   * @return string The extra info of this session
   */
  public function getExtraInfo() {
    return $this->extraInfo;
  }

  /**
   * Set the extra info for this paper
   *
   * @param string $extraInfo The extra info
   */
  public function setExtraInfo($extraInfo) {
    $extraInfo = (($extraInfo !== NULL) && strlen(trim($extraInfo)) > 0) ? trim($extraInfo) : NULL;

    $this->extraInfo = $extraInfo;
    $this->toSave['extraInfo'] = $extraInfo;
  }

  /**
   * Returns a list with ids of all papers added to this session
   *
   * @return int[] The list with ids of all papers added to this session
   */
  public function getPapersId() {
    return $this->papers_id;
  }

  /**
   * Returns the id of this sessions state
   *
   * @return int The session state id
   */
  public function getStateId() {
    return $this->state_id;
  }

  /**
   * Returns this sessions state
   *
   * @return SessionStateApi The session state
   */
  public function getState() {
    if (!$this->sessionState) {
      $sessionStates = CachedConferenceApi::getSessionStates();

      foreach ($sessionStates as $sessionState) {
        if ($sessionState->getId() == $this->state_id) {
          $this->sessionState = $sessionState;
          break;
        }
      }
    }

    return $this->sessionState;
  }

  /**
   * Returns the id of this sessions type
   *
   * @return int The session type id
   */
  public function getTypeId() {
    return $this->type_id;
  }

  /**
   * Returns this sessions type
   *
   * @return SessionTypeApi The session type
   */
  public function getType() {
    if (!$this->sessionType && is_int($this->getTypeId())) {
      $sessionTypes = CachedConferenceApi::getSessionTypes();

      foreach ($sessionTypes as $sessionType) {
        if ($sessionType->getId() == $this->type_id) {
          $this->sessionType = $sessionType;
          break;
        }
      }
    }

    return $this->sessionType;
  }

  /**
   * Returns this sessions different session type
   *
   * @return string|null This sessions different session type
   */
  public function getDifferentType() {
    return $this->differentType;
  }

  /**
   * Sets this sessions different session type
   *
   * @param string|null $differentType This sessions different session type
   */
  public function setDifferentType($differentType) {
    $differentType = (($differentType !== NULL) && strlen(trim($differentType)) > 0) ? trim($differentType) : NULL;

    $this->differentType = $differentType;
    $this->toSave['differentType'] = $differentType;
  }

  /**
   * Returns all the networks to which this session belongs
   *
   * @return NetworkApi[] All networks to which this session belongs
   */
  public function getNetworks() {
    if (!$this->networks) {
      $this->networks = array();

      $networks = CachedConferenceApi::getNetworks();
      foreach ($networks as $network) {
        if (is_int(array_search($network->getId(), $this->getNetworksId()))) {
          $this->networks[] = $network;
        }
      }
    }

    return $this->networks;
  }

  /**
   * Set all the networks to which this session belongs
   *
   * @param int[]|NetworkApi[] $networks The networks (or their ids) to add to this session
   */
  public function setNetworks($networks) {
    $this->networks = NULL;
    $this->networks_id = array();

    foreach ($networks as $network) {
      if ($network instanceof NetworkApi) {
        $this->networks_id[] = $network->getId();
      }
      else {
        if (is_int($network)) {
          $this->networks_id[] = $network;
        }
      }
    }

    $this->toSave['networks.id'] = implode(';', $this->networks_id);
  }

  /**
   * Returns a list with ids of all networks to which this session belongs
   *
   * @return int[] The network ids
   */
  public function getNetworksId() {
    return $this->networks_id;
  }

  /**
   * Returns the user that created this session
   *
   * @return UserApi The user that created this session
   */
  public function getAddedBy() {
    if (!$this->addedBy && is_int($this->getAddedById())) {
      $this->addedBy = CRUDApiMisc::getById(new UserApi(), $this->getAddedById());
    }

    return $this->addedBy;
  }

  /**
   * Set the user who added this session
   *
   * @param int|UserApi $addedBy The user (id)
   */
  public function setAddedBy($addedBy) {
    if ($addedBy instanceof UserApi) {
      $addedBy = $addedBy->getId();
    }

    $this->addedBy = NULL;
    $this->addedBy_id = $addedBy;
    $this->toSave['addedBy.id'] = $addedBy;
  }

  /**
   * The user id of the user who created this session
   *
   * @return int The user id of the user who created this session
   */
  public function getAddedById() {
    return $this->addedBy_id;
  }

  /**
   * Set the state of this session
   *
   * @param int|SessionStateApi $state The session state (id)
   */
  public function setState($state) {
    if ($state instanceof SessionStateApi) {
      $state = $state->getId();
    }

    $this->sessionState = NULL;
    $this->state_id = $state;
    $this->toSave['state.id'] = $state;
  }

  /**
   * Set the type of this session
   *
   * @param int|SessionTypeApi $type The session type (id)
   */
  public function setType($type) {
    if ($type instanceof SessionTypeApi) {
      $type = $type->getId();
    }

    $this->sessionType = NULL;
    $this->type_id = $type;
    $this->toSave['type.id'] = $type;
  }

  /**
   * Returns session participants information of this session
   *
   * @return CombinedSessionParticipantApi[] The session participant information
   */
  public function getSessionParticipantInfo() {
    if (!$this->sessionParticipants) {
      $this->sessionParticipants =
        CRUDApiMisc::getAllWherePropertyEquals(new CombinedSessionParticipantApi(), 'session_id', $this->getId())
          ->getResults();
    }

    return $this->sessionParticipants;
  }

  /**
   * Returns the name of this session
   *
   * @return string The name of this session
   */
  public function getName() {
    return $this->name;
  }

  /**
   * Set the name for this paper
   *
   * @param string $name The name
   */
  public function setName($name) {
    $name = (($name !== NULL) && strlen(trim($name)) > 0) ? trim($name) : NULL;

    $this->name = $name;
    $this->toSave['name'] = $name;
  }

  public function __toString() {
    return $this->getName();
  }
} 