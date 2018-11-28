<?php
namespace Drupal\iish_conference\API\Domain;

use Drupal\iish_conference\API\CRUDApiMisc;
use Drupal\iish_conference\API\CRUDApiClient;

/**
 * Holds a fee state obtained from the API
 */
class FeeStateApi extends CRUDApiClient {
  const NO_FEE_SELECTED = 0;

  protected $name;
  protected $isDefaultFee;
  protected $isStudentFee;
  protected $isAccompanyingPersonFee;
  protected $feeAmounts_id;

  /**
   * Returns the default fee state, if there is one
   *
   * @return FeeStateApi|null The default fee state, if found
   */
  public static function getDefaultFee() {
    return CRUDApiMisc::getFirstWherePropertyEquals(new FeeStateApi(), 'isDefaultFee', TRUE);
  }

  /**
   * Returns the student fee state, if there is one
   *
   * @return FeeStateApi|null The student fee state, if found
   */
  public static function getStudentFee() {
    return CRUDApiMisc::getFirstWherePropertyEquals(new FeeStateApi(), 'isStudentFee', TRUE);
  }

  /**
   * Returns the accompanying person fee state, if there is one
   *
   * @return FeeStateApi|null The accompanying person fee state, if found
   */
  public static function getAccompanyingPersonFee() {
    return CRUDApiMisc::getFirstWherePropertyEquals(new FeeStateApi(), 'isAccompanyingPersonFee', TRUE);
  }

  /**
   * Returns the name of the fee state
   *
   * @return string The fee state name
   */
  public function getName() {
    return $this->name;
  }

  /**
   * Returns the ids of all fee amounts belonging to this fee state
   *
   * @return int[] Returns fee amount ids
   */
  public function getFeeAmountsId() {
    return $this->feeAmounts_id;
  }

  /**
   * Returns whether this fee is the default fee
   *
   * @return bool Returns true if this is the default fee
   */
  public function isDefaultFee() {
    return $this->isDefaultFee;
  }

  /**
   * Returns whether this fee is the student fee
   *
   * @return bool Returns true if this is the student fee
   */
  public function isStudentFee() {
    return $this->isStudentFee;
  }

  /**
   * Returns whether this fee is the accompanying person fee
   *
   * @return bool Returns true if this is the accompanying person fee
   */
  public function isAccompanyingPersonFee() {
    return $this->isAccompanyingPersonFee;
  }

  /**
   * Returns all fee amounts for this fee state
   *
   * @param int|null $date Returns only the fee amounts that are still valid from the given date
   *                                     If no date is given, the current date is used
   * @param int|null $numDays When specified, returns only the fee amounts for this number of days
   * @param bool $oneDateOnly Whether to only return results with the same youngest date
   *
   * @return FeeAmountApi[] The fee amounts that match the criteria
   */
  public function getFeeAmounts($date = NULL, $numDays = NULL, $oneDateOnly = TRUE) {
    return FeeAmountApi::getFeeAmounts($this, $date, $numDays, $oneDateOnly);
  }

  public function __toString() {
    return $this->getName();
  }
} 