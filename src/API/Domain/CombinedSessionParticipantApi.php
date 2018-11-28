<?php
namespace Drupal\iish_conference\API\Domain;

use Drupal\iish_conference\API\ApiCriteriaBuilder;

/**
 * Holds a combined session participant obtained from the API
 */
class CombinedSessionParticipantApi extends SessionParticipantApi {
  protected $sessionParticipant_id;

  /**
   * Even though none of the ids can be null, querying it like this triggers a
   * join This join makes sure that instances with removed sessions, types or
   * users are filtered out. Allows the user to get a list with instances of
   * this class based on a list with criteria
   *
   * @param array $properties The criteria
   *
   * @return mixed|null
   */
  public static function getListWithCriteria(array $properties) {
    $prop = new ApiCriteriaBuilder();
    $properties = array_merge($prop
      ->ne('session_id', NULL)
      ->ne('user_id', NULL)
      ->ne('type_id', NULL)
      ->get(),
      $properties);

    return parent::getListWithCriteria($properties);
  }

  /**
   * The session participant
   *
   * @return SessionParticipantApi The session participant
   */
  public function getSessionParticipant() {
    if ($this->sessionParticipant_id !== NULL) {
      return SessionParticipantApi::createNewInstance(array_merge(
        get_object_vars($this),
        ['id' => $this->sessionParticipant_id]
      ));
    }
    return NULL;
  }

  /**
   * For the given list with combined session participants, filter out all
   * actual session participants
   *
   * @param CombinedSessionParticipantApi[] $sessionParticipants The list with
   *   combined session participants
   *
   * @return SessionParticipantApi[] The session participants
   */
  public static function getAllSessionParticipants($sessionParticipants) {
    $sp = [];
    foreach ($sessionParticipants as $sessionParticipant) {
      if ($sessionParticipant->getSessionParticipant() !== NULL) {
        $sp[] = $sessionParticipant->getSessionParticipant();
      }
    }
    return array_values($sp);
  }
} 