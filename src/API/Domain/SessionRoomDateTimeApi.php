<?php
namespace Drupal\iish_conference\API\Domain;

use Drupal\iish_conference\API\CRUDApiClient;

/**
 * Holds a session room date time obtained from the API
 */
class SessionRoomDateTimeApi extends CRUDApiClient {
  protected $room_roomName;
  protected $room_roomNumber;
  protected $session_id;
  protected $sessionDateTime_indexNumber;
  protected $sessionDateTime_day;
  protected $sessionDateTime_period;

  private $day;
  
  /**
   * Returns the name of the room where the session is currently planned
   *
   * @return string The room name
   */
  public function getRoomName() {
    return $this->room_roomName;
  }

  /**
   * Returns the number of the room where the session is currently planned
   *
   * @return string The room number
   */
  public function getRoomNumber() {
    return $this->room_roomNumber;
  }

  /**
   * Returns the id of the session in question
   *
   * @return int The session id
   */
  public function getSessionId() {
    return $this->session_id;
  }

  /**
   * Returns the day this session is planned
   *
   * @return DayApi The day this session is planned
   */
  public function getDay() {
    if (!$this->day) {
      $this->day = DayApi::createNewInstance($this->sessionDateTime_day);
    }

    return $this->day;
  }

  /**
   * Returns the time slot index number the session is currently planned
   *
   * @return int The index number
   */
  public function getIndexNumber() {
    return $this->sessionDateTime_indexNumber;
  }

  /**
   * Returns the date/time period the session is currently planned
   *
   * @return string The date/time period
   */
  public function getDateTimePeriod() {
    return $this->sessionDateTime_period;
  }
} 