<?php
namespace Drupal\iish_conference\API\Domain;

use Drupal\iish_conference\ConferenceMisc;
use Drupal\iish_conference\API\CRUDApiMisc;
use Drupal\iish_conference\API\SettingsApi;
use Drupal\iish_conference\API\CRUDApiClient;
use Drupal\iish_conference\API\ApiCriteriaBuilder;

/**
 * Holds a fee amount obtained from the API
 */
class FeeAmountApi extends CRUDApiClient {
  protected $feeState_id;
  protected $endDate;
  protected $numDaysStart;
  protected $numDaysEnd;
  protected $feeAmount;
  protected $feeAmountOnSite;
  protected $substituteName;

  private $feeState;

  /**
   * Returns all fee amounts
   *
   * @param FeeStateApi|int $feeState The fee state (id) to use
   * @param int|null $date Returns only the fee amounts that are still valid from the given date
   *                                     If no date is given, the current date is used
   * @param int|null $numDays When specified, returns only the fee amounts for this number of days
   * @param bool $oneDateOnly Whether to only return results with the same youngest date
   *
   * @return FeeAmountApi[] The fee amounts that match the criteria
   */
  public static function getFeeAmounts($feeState = NULL, $date = NULL, $numDays = NULL, $oneDateOnly = TRUE) {
    if ($feeState instanceof FeeStateApi) {
      $feeState = $feeState->getId();
    }

    if ($date === NULL) {
      $date = strtotime('today');
    }

    $props = new ApiCriteriaBuilder();
    $props
      ->eq('feeState_id', $feeState)
      ->ge('endDate', $date)
      ->sort('endDate', 'asc');

    if (is_int($numDays)) {
      $props
        ->le('numDaysStart', $numDays)
        ->ge('numDaysEnd', $numDays);
    }

    $feeAmounts = FeeAmountApi::getListWithCriteria($props->get())
      ->getResults();

    if ($oneDateOnly) {
      $firstDate = NULL;
      foreach ($feeAmounts as $key => $feeAmount) {
        if ($firstDate === NULL) {
          $firstDate = $feeAmount->getEndDate();
        }
        else {
          if ($firstDate !== $feeAmount->getEndDate()) {
            unset($feeAmounts[$key]);
          }
        }
      }
    }

    return array_values($feeAmounts);
  }

  /**
   * Returns a fee amount description based on the amount of fee amounts given.
   * If more than one fee amount is given, then the days when the fees are valid are included in the description
   *
   * @param FeeAmountApi|FeeAmountApi[] $feeAmounts One or more fee amounts to create a description of
   *
   * @return string The description of the given fee amounts
   */
  public static function getFeeAmountsDescription($feeAmounts) {
    if (is_array($feeAmounts) && (count($feeAmounts) > 1)) {
      return implode(', ', $feeAmounts);
    }
    else {
      $feeAmount = $feeAmounts;
      if (is_array($feeAmounts) && isset($feeAmounts[0])) {
        $feeAmount = $feeAmounts[0];
      }

      return $feeAmount->getDescriptionWithoutDays();
    }
  }

  /**
   * The final date this fee amount is valid
   *
   * @return int The final date as a Unix timestamp
   */
  public function getEndDate() {
    return strtotime($this->endDate);
  }

  /**
   * The fee amount
   *
   * @return float The fee amount
   */
  public function getFeeAmount() {
    return $this->feeAmount;
  }

  /**
   * The fee amount in a human friendly readable format
   *
   * @return string The fee amount
   */
  public function getFeeAmountInFormat() {
    return ConferenceMisc::getReadableAmount($this->feeAmount);
  }

  /**
   * The fee amount (on site)
   *
   * @return float The fee amount (on site)
   */
  public function getFeeAmountOnSite() {
    return $this->feeAmountOnSite;
  }

  /**
   * The fee amount (on site) in a human friendly readable format
   *
   * @return string The fee amount (on site)
   */
  public function getFeeAmountOnSiteInFormat() {
    return ConferenceMisc::getReadableAmount($this->feeAmountOnSite);
  }

  /**
   * The id of the fee state to which this amount belongs
   *
   * @return int The fee state id
   */
  public function getFeeStateId() {
    return $this->feeState_id;
  }

  /**
   * The fee state to which this amount belongs
   *
   * @return FeeStateApi The fee state
   */
  public function getFeeState() {
    if (!$this->feeState) {
      $this->feeState = CRUDApiMisc::getById(new FeeStateApi(), $this->feeState_id);
    }

    return $this->feeState;
  }

  /**
   * Return the maximum number of days for which this fee amount is valid
   *
   * @return int The max number of days
   */
  public function getNumDaysEnd() {
    return $this->numDaysEnd;
  }

  /**
   * Return the minimum number of days for which this fee amount is valid
   *
   * @return int The min number of days
   */
  public function getNumDaysStart() {
    return $this->numDaysStart;
  }

  /**
   * Returns the substitute name (over the fee state name) if this fee amount is used
   *
   * @return string|null The substitute name, if it exists
   */
  public function getSubstituteName() {
    return $this->substituteName;
  }

  /**
   * Returns a description of the current fee amount
   *
   * @return string Returns the name of the fee, which days the fee is valid and the fee amount
   */
  public function getDescription() {
    $description = NULL;

    if ($this->numDaysStart == $this->numDaysEnd) {
      $days = $this->numDaysStart . ' ' . iish_t('day');
    }
    else {
      $days = $this->numDaysStart . '-' . $this->numDaysEnd . ' ' . iish_t('days');
    }

    $name = $this->getFeeState()->getName();
    if (!empty($this->substituteName)) {
      $name = $this->substituteName;
    }

    if ($this->getFeeState()->isAccompanyingPersonFee()) {
      $description = '(' . $days . '): ' . $this->getFeeAmountInFormat();
    }
    else {
      $description = $name . ' (' . $days . '): ' . $this->getFeeAmountInFormat();
    }

    if (SettingsApi::getSetting(SettingsApi::PAYMENT_ON_SITE_STARTDATE, 'startdate')) {
      $description .= ' (' . iish_t('If payed on site') . ': ' . $this->getFeeAmountOnSiteInFormat() . ')';
    }

    return $description;
  }

  /**
   * Returns a description of the current fee amount without the days
   *
   * @return string Returns the name of the fee and the fee amount
   */
  public function getDescriptionWithoutDays() {
    $description = NULL;

    $name = $this->getFeeState()->getName();
    if (!empty($this->substituteName)) {
      $name = $this->substituteName;
    }

    if ($this->getFeeState()->isAccompanyingPersonFee()) {
      $description = $this->getFeeAmountInFormat();
    }
    else {
      $description = $name . ': ' . $this->getFeeAmountInFormat();
    }

    if (SettingsApi::getSetting(SettingsApi::PAYMENT_ON_SITE_STARTDATE, 'startdate')) {
      $description .= ' (' . iish_t('If payed on site') . ': ' . $this->getFeeAmountOnSiteInFormat() . ')';
    }

    return $description;
  }

  public function __toString() {
    return $this->getDescription();
  }
} 
