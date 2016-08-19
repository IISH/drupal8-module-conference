<?php
namespace Drupal\iish_conference\API;

/**
 * Miscellaneous CRUD API functions that are often used
 */
class CRUDApiMisc {

  /**
   * Returns the instance of the given object with the given id
   *
   * @param CRUDApiClient $obj The object to return an instance of with the given id
   * @param int $id The id to look for
   *
   * @return CRUDApiClient|null The instance or null if not found
   */
  public static function getById($obj, $id) {
    return self::getFirstWherePropertyEquals($obj, 'id', $id);
  }

  /**
   * Returns the first instance of the given object where the given property equals the given id
   *
   * @param CRUDApiClient $obj The object to return the first instance of
   * @param string $property The property in question
   * @param mixed $value The value the given property should have
   *
   * @return CRUDApiClient|null The instance or null if not found
   */
  public static function getFirstWherePropertyEquals($obj, $property, $value) {
    return self::getAllWherePropertyEquals($obj, $property, $value)
      ->getFirstResult();
  }

  /**
   * Returns all matching instances of the given object where the given property equals the given id
   *
   * @param CRUDApiClient $obj The object to return instances of
   * @param string $property The property in question
   * @param mixed $value The value the given property should have
   *
   * @return CRUDApiResults The results
   */
  public static function getAllWherePropertyEquals($obj, $property, $value) {
    $props = new ApiCriteriaBuilder();

    return $obj->getListWithCriteria(
      $props
        ->eq($property, $value)
        ->get()
    );
  }
} 