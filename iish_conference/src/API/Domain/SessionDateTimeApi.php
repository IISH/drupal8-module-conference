<?php
namespace Drupal\iish_conference\API\Domain;

use Drupal\iish_conference\API\CRUDApiClient;
use Drupal\iish_conference\API\CachedConferenceApi;

/**
 * Holds a session date/time obtained from the API
 */
class SessionDateTimeApi extends CRUDApiClient {
  protected $day_id;
  protected $indexNumber;
  protected $period;

  private $day;

  /**
   * Returns the id of the day that this session date/time time slot is
   *
   * @return int The day id
   */
  public function getDayId() {
    return $this->day_id;
  }

  /**
   * Returns the index number given to this time slot
   *
   * @return int The index number
   */
  public function getIndexNumber() {
    return $this->indexNumber;
  }

  /**
   * Returns the period between which the time slot takes place
   *
   * @param bool $extraSpacing Whether there has to be extra spacing between the dash and the start and end time
   *
   * @return string The period
   */
  public function getPeriod($extraSpacing = FALSE) {
    $period = $this->period;
    if ($extraSpacing) {
      $period = str_replace('-', ' - ', $period);
      $period = str_replace('  ', ' ', $period);
    }

    return $period;
  }

  /**
   * Returns the period between which the time slot takes place with extra spacing.
   * 
   * @return string The period with extra spacing.
   */
  public function getPeriodExtraSpacing() {
    return $this->getPeriod(TRUE);
  }

  /**
   * Returns the day that this session date/time time slot is
   *
   * @return DayApi The day of this session date/time slot
   */
  public function getDay() {
    if (!$this->day) {
      $days = CachedConferenceApi::getDays();

      foreach ($days as $day) {
        if ($day->getId() == $this->day_id) {
          $this->day = $day;
          break;
        }
      }
    }

    return $this->day;
  }

  public function __toString() {
    return $this->getIndexNumber() . ': ' . $this->getPeriod();
  }
}
