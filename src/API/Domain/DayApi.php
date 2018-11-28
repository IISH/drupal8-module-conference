<?php
namespace Drupal\iish_conference\API\Domain;

use Drupal\iish_conference\API\CRUDApiClient;

/**
 * Holds a day obtained from the API
 */
class DayApi extends CRUDApiClient {
  protected $day;
  protected $dayNumber;

  /**
   * Returns the number of this day
   *
   * @return int This day number
   */
  public function getDayNumber() {
    return $this->dayNumber;
  }

  /**
   * Returns this day in a more human-friendly readable format
   *
   * @param string $format The format to use
   *
   * @return string This day in a more human-friendly readable format
   */
  public function getDayFormatted($format = 'Y-m-d') {
    return date($format, $this->getDay());
  }

  /**
   * Returns this day as a Unix timestamp
   *
   * @return int The Unix timestamp of this day
   */
  public function getDay() {
    return strtotime($this->day);
  }

  public function __toString() {
    return iish_t('Day') . ' ' . $this->getDayNumber() . ': ' . $this->getDayFormatted('l j F Y');
  }
} 