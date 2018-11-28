<?php
namespace Drupal\iish_conference\API\Domain;

use Drupal\iish_conference\API\CRUDApiClient;

/**
 * Holds a sent email obtained from the API
 */
class SentEmailApi extends CRUDApiClient {
  private static $sortOnCreated = FALSE;

  protected $user_id;
  protected $fromName;
  protected $fromEmail;
  protected $subject;
  protected $body;
  protected $dateTimeCreated;
  protected $dateTimeSent;
  protected $dateTimesSentCopy;
  protected $sendAsap;
  protected $numTries;

  /**
   * Whether to sort on date/time created or on date/time sent
   *
   * @return boolean True if sorted on date/time created
   */
  public static function getSortOnCreated() {
    return self::$sortOnCreated;
  }

  /**
   * Set whether to sort on date/time created or on date/time sent
   *
   * @param boolean $sortOnCreated True if sorted on date/time created
   */
  public static function setSortOnCreated($sortOnCreated) {
    self::$sortOnCreated = $sortOnCreated;
  }

  /**
   * Returns the email body
   *
   * @return string The email body
   */
  public function getBody() {
    return $this->body;
  }

  /**
   * Returns a Unix timestamp of the date/time this email was created
   *
   * @return int|null The Unix timestamp this email was created
   */
  public function getDateTimeCreated() {
    if ($this->dateTimeCreated === NULL) {
      return NULL;
    }
    else {
      return strtotime($this->dateTimeCreated);
    }
  }

  /**
   * Returns the date/time this email was created with the given format
   *
   * @param string $format The date/time format
   *
   * @return string|null The date/time according to the format
   */
  public function getDateTimeCreatedFormatted($format = 'Y-m-d H:i:s') {
    if ($this->dateTimeCreated === NULL) {
      return NULL;
    }
    else {
      return date($format, $this->getDateTimeCreated());
    }
  }

  /**
   * Returns a Unix timestamp of the date/time this email was successfully sent
   *
   * @return int|null The Unix timestamp this email was successfully sent
   */
  public function getDateTimeSent() {
    if ($this->dateTimeSent === NULL) {
      return NULL;
    }
    else {
      return strtotime($this->dateTimeSent);
    }
  }

  /**
   * Returns the date/time this email was successfully sent with the given format
   *
   * @param string $format The date/time format
   *
   * @return string|null The date/time according to the format
   */
  public function getDateTimeSentFormatted($format = 'Y-m-d H:i:s') {
    if ($this->dateTimeSent === NULL) {
      return NULL;
    }
    else {
      return date($format, $this->getDateTimeSent()) . ' (CET)';
    }
  }

  /**
   * Returns Unix timestamps of the dates/times copies of this email were successfully sent
   *
   * @return int[]|null The Unix timestamps copies of this email were successfully sent
   */
  public function getDateTimesSentCopy() {
    if ($this->dateTimesSentCopy === NULL) {
      return NULL;
    }

    $unixTimes = array();
    $datesTimes = explode(';', $this->dateTimesSentCopy);
    foreach ($datesTimes as $dateTime) {
      $unixTimes[] = strtotime($dateTime);
    }

    return $unixTimes;
  }

  /**
   * Returns the dates/times copies of this email were successfully sent with the given format
   *
   * @param string $format The date/time format
   *
   * @return string[]|null The dates/times according to the format
   */
  public function getDateTimesSentCopyFormatted($format = 'Y-m-d H:i:s') {
    if ($this->dateTimesSentCopy === NULL) {
      return NULL;
    }

    $datesTimesNew = array();
    $datesTimes = explode(';', $this->dateTimesSentCopy);
    foreach ($datesTimes as $time) {
      if (strlen(trim($time)) > 0) {
        $datesTimesNew[] = date($format, $time) . ' (CET)';
      }
    }

    return $datesTimesNew;
  }

  /**
   * Returns the email address of the person who send this email
   *
   * @return string The email address of the person who send this email
   */
  public function getFromEmail() {
    return $this->fromEmail;
  }

  /**
   * Returns the name of the person who send this email
   *
   * @return string The name of the person who send this email
   */
  public function getFromName() {
    return $this->fromName;
  }

  /**
   * Returns how many times the CMS has tried to sent this email
   *
   * @return int The number of tries
   */
  public function getNumTries() {
    return $this->numTries;
  }

  /**
   * Returns whether this email has to be send as soon as possible
   *
   * @return bool Whether this email has to be send as soon as possible
   */
  public function getSendAsap() {
    return $this->sendAsap;
  }

  /**
   * Returns the subject of this email
   *
   * @return string The subject of this email
   */
  public function getSubject() {
    return $this->subject;
  }

  /**
   * Returns the id of the user to whom this email is addressed
   *
   * @return int The id of the user to whom this email is addressed
   */
  public function getUserId() {
    return $this->user_id;
  }

  /**
   * Compare two emails, first by date, then by subject
   *
   * @param SentEmailApi $instance Compare this instance with the given instance
   *
   * @return int &lt; 0 if <i>$instA</i> is less than
   * <i>$instB</i>; &gt; 0 if <i>$instA</i>
   * is greater than <i>$instB</i>, and 0 if they are
   * equal.
   */
  protected function compareWith($instance) {
    if (self::getSortOnCreated()) {
      $dateCmp = strcmp($instance->getDateTimeCreated(), $this->getDateTimeCreated());
    }
    else {
      $dateCmp = strcmp($instance->getDateTimeSent(), $this->getDateTimeSent());
    }

    if ($dateCmp === 0) {
      return strcmp(strtolower($this->getSubject()), strtolower($instance->getSubject()));
    }
    else {
      return $dateCmp;
    }
  }

  public function __toString() {
    return $this->getSubject();
  }
} 