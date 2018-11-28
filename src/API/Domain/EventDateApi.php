<?php
namespace Drupal\iish_conference\API\Domain;

use Drupal\iish_conference\API\CRUDApiClient;

/**
 * Holds an event date obtained from the API
 */
class EventDateApi extends CRUDApiClient {
  protected $yearCode;
  protected $startDate;
  protected $endDate;
  protected $dateAsText;
  protected $description;
  protected $longDescription;
  protected $lastDate;
  protected $event;

  private $eventInstance;

  private static $instance;
  private static $eventDates;

  /**
   * There is only one event date, the current, get the singleton instance
   *
   * @return EventDateApi The current event date
   */
  public static function getCurrent() {
    if (self::$instance === NULL) {
      $eventDateInfo = parent::getClient()->get('eventDateInfo', array());
      self::$instance = self::createNewInstance($eventDateInfo);
    }

    return self::$instance;
  }

  /**
   * Retrieve all of the event dates for this event
   *
   * @return EventDateApi[] All of the event dates for this event
   */
  public static function getAllForEvent() {
    if (self::$eventDates === NULL) {
      self::$eventDates = array();
      $eventDates = parent::getClient()->get('eventDates', array());
      foreach ($eventDates as $eventDate) {
        self::$eventDates[] = self::createNewInstance($eventDate);
      }
    }

    return self::$eventDates;
  }

  /**
   * Returns start and end date of the current event date as text
   *
   * @return string Start and end date of the current event date as text
   */
  public function getDateAsText() {
    return $this->dateAsText;
  }

  /**
   * Returns a description of the current event date
   *
   * @return string A description of the current event date
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * Returns the end date of the current event date
   *
   * @return int The end date of the current event date as a Unix timestamp
   */
  public function getEndDate() {
    return strtotime($this->endDate);
  }

  /**
   * Returns the end date of the current event date in a more human-friendly readable format
   *
   * @param string $format The format to use
   *
   * @return string The end date of the current event date in a more human-friendly readable format
   */
  public function getEndDateFormatted($format = 'Y-m-d') {
    return date($format, $this->getEndDate());
  }

  /**
   * Returns a long description of the current event date
   *
   * @return string A long description of the current event date
   */
  public function getLongDescription() {
    return $this->longDescription;
  }

  /**
   * Returns the start date of the current event date
   *
   * @return int The start date of the current event date as a Unix timestamp
   */
  public function getStartDate() {
    return strtotime($this->startDate);
  }

  /**
   * Returns the start date of the current event date in a more human-friendly readable format
   *
   * @param string $format The format to use
   *
   * @return string The start date of the current event date in a more human-friendly readable format
   */
  public function getStartDateFormatted($format = 'Y-m-d') {
    return date($format, $this->getStartDate());
  }

  /**
   * Returns the year code of this event date
   *
   * @return string The year code of this event date
   */
  public function getYearCode() {
    return $this->yearCode;
  }

  /**
   * Returns the year code of this event date for a URL
   *
   * @return string The year code of this event date for a URL
   */
  public function getYearCodeURL() {
    return preg_replace('/\s+/', '-', $this->yearCode);
  }

  /**
   * Indicates whether this event date is also the latest date
   *
   * @return bool Whether this event date is also the latest date
   */
  public function isLastDate() {
    return $this->lastDate;
  }

  /**
   * Returns the event to which this event date belongs to
   *
   * @return EventApi The event to which this event date belongs to
   */
  public function getEvent() {
    if (!$this->eventInstance) {
      $this->eventInstance = EventApi::createNewInstance($this->event);
    }

    return $this->eventInstance;
  }

  /**
   * Returns the code and year of this event date
   *
   * @return string The code and year of this event date concatenated by a space
   */
  public function getShortNameAndYear() {
    return $this->getEvent()->getShortName() . ' ' . $this->getYearCode();
  }

  /**
   * Returns the long code (including the word 'conference') and year of this event date
   *
   * @return string The long code (including the word 'conference') and year of this event date concatenated
   */
  public function getLongNameAndYear() {
    return $this->getEvent()->getLongName() . ' ' . $this->getYearCode();
  }

  public function __toString() {
    return $this->getShortNameAndYear();
  }
} 