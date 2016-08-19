<?php
namespace Drupal\iish_conference\API;

/**
 * Allows for easy creation of criteria for API calls
 */
class ApiCriteriaBuilder {
  private static $allowedMethods = array('eq', 'ne', 'gt', 'lt', 'ge', 'le');
  private static $otherProperties = array('sort', 'order', 'max', 'offset');

  private $criteria;

  public function __construct() {
    $this->criteria = array();
  }

  /**
   * Properties should equal (==) the given value
   *
   * @param string $property The name of the property to apply criteria on
   * @param mixed $value The value this property should be equal to
   *
   * @return $this Itself, to continue adding criteria
   */
  public function eq($property, $value) {
    $this->addCriteria($property, $value, $method = 'eq');

    return $this;
  }

  /**
   * Properties should not equal (!=) the given value
   *
   * @param string $property The name of the property to apply criteria on
   * @param mixed $value The value this property should not be equal to
   *
   * @return $this Itself, to continue adding criteria
   */
  public function ne($property, $value) {
    $this->addCriteria($property, $value, $method = 'ne');

    return $this;
  }

  /**
   * Properties should be greater than (>) the given value
   *
   * @param string $property The name of the property to apply criteria on
   * @param mixed $value The value this property should be greater than
   *
   * @return $this Itself, to continue adding criteria
   */
  public function gt($property, $value) {
    $this->addCriteria($property, $value, $method = 'gt');

    return $this;
  }

  /**
   * Properties should be lower than (<) the given value
   *
   * @param string $property The name of the property to apply criteria on
   * @param mixed $value The value this property should be lower than
   *
   * @return $this Itself, to continue adding criteria
   */
  public function lt($property, $value) {
    $this->addCriteria($property, $value, $method = 'lt');

    return $this;
  }

  /**
   * Properties should be greater than or equal (>=) the given value
   *
   * @param string $property The name of the property to apply criteria on
   * @param mixed $value The value this property should be greater than or equal
   *
   * @return $this Itself, to continue adding criteria
   */
  public function ge($property, $value) {
    $this->addCriteria($property, $value, $method = 'ge');

    return $this;
  }

  /**
   * Properties should be lower than or equal (<=) the given value
   *
   * @param string $property The name of the property to apply criteria on
   * @param mixed $value The value this property should be lower than or equal
   *
   * @return $this Itself, to continue adding criteria
   */
  public function le($property, $value) {
    $this->addCriteria($property, $value, $method = 'le');

    return $this;
  }

  /**
   * The property on which the results should be sorted
   * Can only be used on one property, so only the last call on this method counts
   *
   * @param string $property The property to sort on
   * @param string $order The order to sort on, either 'asc' or 'desc'
   *
   * @return $this Itself, to continue adding criteria
   */
  public function sort($property, $order = 'asc') {
    if (($order == 'asc') || ($order == 'desc')) {
      $this->addCriteria('sort', $property);
      $this->addCriteria('order', $order);
    }

    return $this;
  }

  /**
   * When many results are expected and divided over multiple pages
   * Allows for only obtaining a subset of all the results
   *
   * @param int $max The maximum number of instances to return
   * @param int $offset The offset, how many instances should be skipped?
   *
   * @return $this Itself, to continue adding criteria
   */
  public function paginate($max, $offset = 0) {
    $max = (is_int($max) && ($max <= 50)) ? $max : 50;
    $offset = (is_int($offset) && ($offset >= 0)) ? $offset : 0;

    $this->addCriteria('max', $max);
    $this->addCriteria('offset', $offset);

    return $this;
  }

  /**
   * Close this builder and obtain the criteria to be send with the API call
   *
   * @return array The criteria for the API call
   */
  public function get() {
    return $this->criteria;
  }

  /**
   * Adds a criterion to the criteria array
   *
   * @param string $property The name of the property to apply criteria on
   * @param mixed $value The value this property in the criterion
   * @param string|null $method The method to apply
   */
  private function addCriteria($property, $value, $method = NULL) {
    $value = (is_null($value)) ? 'null' : $value;

    if (in_array(trim($method), self::$allowedMethods)) {
      $this->criteria[] =
        array(
          'property' => trim($property),
          'value' => trim($value),
          'method' => trim($method)
        );
    }
    else {
      if (in_array(trim($property), self::$otherProperties)) {
        $this->criteria[] = array(
          'property' => trim($property),
          'value' => trim($value),
          'method' => $method
        );
      }
    }
  }
}