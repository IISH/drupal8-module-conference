<?php
namespace Drupal\iish_conference\API;

use Drupal\iish_conference\EasyProtection;

/**
 * The Conference API client for standard CRUD (Create, Read, Update and Delete) actions
 */
abstract class CRUDApiClient {
  private static $allowedMethods = array('eq', 'ne', 'gt', 'lt', 'ge', 'le');
  private static $otherProperties = array('sort', 'order', 'max', 'offset');
  private static $client;

  protected $id;
  protected $toSave = array();

  /**
   * Returns a list with CRUDApiClient instances as a key value array with the id as the key
   * and the string representation of the instance as a value
   *
   * @param CRUDApiClient[] $crudList A list with CRUDApiClient instances
   *
   * @return array An array with the id a s the key and the string representation of the instance as a value
   */
  public static function getAsKeyValueArray(array $crudList) {
    $list = array();
    foreach ($crudList as $crudInstance) {
      $list[$crudInstance->getId()] = iish_t($crudInstance->__toString());
    }

    return $list;
  }

  /**
   * Returns a list with the ids of the given list with CRUDApiClient instances
   *
   * @param CRUDApiClient[] $crudList A list with CRUDApiClient instances
   *
   * @return int[] All the ids
   */
  public static function getIds(array $crudList) {
    $list = array();
    foreach ($crudList as $crudInstance) {
      $list[] = $crudInstance->getId();
    }

    return $list;
  }

  /**
   * Peforms the given method on each element in the list
   *
   * @param CRUDApiClient[] $crudList A list with CRUDApiClient instances
   * @param string $methodName The name of the method in question
   *
   * @return array The results of the given method call on each instance of the given list
   */
  public static function getForMethod(array $crudList, $methodName) {
    $list = array();
    foreach ($crudList as $crudInstance) {
      $list[] = $crudInstance->$methodName();
    }

    return $list;
  }

  /**
   * Return the previous and next item in a list of records
   *
   * @param CRUDApiClient[] $crudList All records ordered
   * @param CRUDApiClient $cur The current record, find out what is the previous and next record of this one
   *
   * @return CRUDApiClient[] An array with the previous record and the next record
   */
  public static function getPrevNext(array $crudList, $cur) {
    $found = FALSE;
    $prev = NULL;
    $next = NULL;
    $tmp = NULL;

    foreach ($crudList as $record) {
      if ($found) {
        $next = $record;
        break;
      }

      if ($record->getId() === $cur->getId()) {
        $prev = $tmp;
        $found = TRUE;
      }

      $tmp = $record;
    }

    return array($prev, $next);
  }

  /**
   * Sort a list with CRUDApiClient instances
   *
   * @param CRUDApiClient[] $crudList The instances to be sorted
   */
  public static function sort(&$crudList) {
    if (is_array($crudList)) {
      usort($crudList, array('self', 'compare'));
    }
  }

  /**
   * Allows the user to get a list with instances of this class based on a list with criteria
   *
   * @param array $properties The criteria
   *
   * @return mixed|null
   */
  public static function getListWithCriteria(array $properties) {
    $class = (new \ReflectionClass(get_called_class()))->getShortName();
    $apiName = substr($class, 0, -3);
    $apiResults =
      self::getClient()
        ->get($apiName, self::transformCRUDParametersToApi($properties));

    $results = new CRUDApiResults($apiResults['totalSize']);
    foreach ($apiResults['results'] as $curInstance) {
      $results->addToResults(self::createNewInstance($curInstance));
    }

    return $results;
  }

  /**
   * Translates the list of parameters given by the user to a list of parameters the API will understand:
   * - '_' are translated to '.'
   * - The property becomes the key
   * - The value now consists of a concatenation of the value and the method (concatenated by '::')
   *
   * @param array $crudParameters The parameters as given by the user
   *
   * @return array The parameters for the API
   */
  protected static function transformCRUDParametersToApi(array $crudParameters) {
    $apiParameters = array();
    foreach ($crudParameters as $parameter) {
      $property = trim(str_replace('_', '.', $parameter['property']));
      $value = (is_null($parameter['value'])) ? 'null' : trim($parameter['value']);
      $method = trim($parameter['method']);

      if (in_array($method, self::$allowedMethods)) {
        $apiParameters[$property] = $value . '::' . $method;
      }
      else {
        if (in_array($property, self::$otherProperties)) {
          $apiParameters[$property] = $value;
        }
      }
    }

    return $apiParameters;
  }

  /**
   * From the results obtained by the API, create a class instance
   *
   * @param array $properties The obtained properties to create the class instance
   *
   * @return mixed A new class instance
   */
  protected static function createNewInstance(array $properties) {
    $class = get_called_class();
    $instance = new $class(FALSE);
    foreach ($properties as $property => $value) {
      $prop = str_replace('.', '_', $property);
      if (is_array($value)) {
        asort($value);
      }
      $instance->$prop = $value;
    }

    return $instance;
  }

  /**
   * Returns a new Api client to use for communication with the Conference Management System API
   *
   * @return ConferenceApiClient The conference client
   */
  protected static function getClient() {
    if (!self::$client) {
      self::$client = new ConferenceApiClient();
    }

    return self::$client;
  }

  /**
   * Compare two CRUDApiClient instances
   *
   * @param CRUDApiClient $instA
   * @param CRUDApiClient $instB
   *
   * @return int &lt; 0 if <i>$instA</i> is less than
   * <i>$instB</i>; &gt; 0 if <i>$instA</i>
   * is greater than <i>$instB</i>, and 0 if they are
   * equal.
   */
  private static function compare($instA, $instB) {
    return $instA->compareWith($instB);
  }

  /**
   * The id of this instance
   *
   * @return int The id of this instance
   */
  public function getId() {
    return $this->id;
  }

  /**
   * Persist all changes made to the database via the API
   *
   * @return bool Whether the save was successful or not
   */
  public function save() {
    $class = (new \ReflectionClass(get_called_class()))->getShortName();
    $apiName = substr($class, 0, -3);
    $this->toSave['id'] = $this->getId();

    if ($this->isUpdate()) {
      $apiResults =
        self::getClient()->post($apiName, $this->toSave);
    }
    else {
      // If the object is created for the first time, store which user created the object
      if (property_exists($this, 'addedBy_id')) {
        $this->addedBy_id = LoggedInUserDetails::getId();
        $this->toSave['addedBy.id'] = LoggedInUserDetails::getId();
      }

      $apiResults =
        self::getClient()->put($apiName, $this->toSave);

      // Set the id this instance was given
      if (is_array($apiResults) && $apiResults['success'] && $apiResults['id']) {
        $this->id = EasyProtection::easyIntegerProtection($apiResults['id']);
      }
    }
    $this->toSave = array();

    return is_array($apiResults) ? $apiResults['success'] : FALSE;
  }

  /**
   * Remove the current instance from the database via the API
   *
   * @return bool Whether the deletion was successful or not
   */
  public function delete() {
    $apiResults = NULL;

    // New instances are not persisted yet, and thus cannot be deleted
    if ($this->isUpdate()) {
      $class = (new \ReflectionClass(get_called_class()))->getShortName();
      $apiName = substr($class, 0, -3);
      $apiResults =
        self::getClient()
          ->delete($apiName, array('id' => $this->getId()));

      // Remove the id, if the delete was successful
      if (is_array($apiResults) && $apiResults['success']) {
        $this->id = NULL;
      }
    }

    return is_array($apiResults) ? $apiResults['success'] : FALSE;
  }

  /**
   * Indicates whether this save is an update or an insert
   *
   * @return bool Returns 'true' in the case of an update
   */
  public function isUpdate() {
    return ($this->getId() !== NULL);
  }

  /**
   * The default comparison of two CRUDApiClient instances, by id
   *
   * @param CRUDApiClient $instance Compare this instance with the given instance
   *
   * @return int &lt; 0 if <i>$instA</i> is less than
   * <i>$instB</i>; &gt; 0 if <i>$instA</i>
   * is greater than <i>$instB</i>, and 0 if they are
   * equal.
   */
  protected function compareWith($instance) {
    return strcmp($this->getId(), $instance->getId());
  }

  public function __toString() {
    return $this->getId();
  }
} 