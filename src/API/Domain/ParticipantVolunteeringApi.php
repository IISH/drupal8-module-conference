<?php
namespace Drupal\iish_conference\API\Domain;

use Drupal\iish_conference\API\CRUDApiMisc;
use Drupal\iish_conference\API\CRUDApiClient;

/**
 * Holds a participant volunteering obtained from the API
 */
class ParticipantVolunteeringApi extends CRUDApiClient {
  protected $participantDate_id;
  protected $volunteering_id;
  protected $network_id;
  protected $volunteering;
  protected $network;
  protected $participantDate;
  protected $participantDate_user;

  private $volunteeringInstance;
  private $networkInstance;
  private $participantDateInstance;
  private $userInstance;

  /**
   * Filter out the list with participant volunteerings based on a certain volunteering type
   *
   * @param ParticipantVolunteeringApi[] $participantVolunteerings The list to filter
   * @param int $volunteeringId The volunteering id to filter on
   *
   * @return ParticipantVolunteeringApi[] The filtered list with participant volunteerings
   */
  public static function getAllNetworksForVolunteering($participantVolunteerings, $volunteeringId) {
    $networks = array();
    foreach ($participantVolunteerings as $participantVolunteering) {
      if ($participantVolunteering->getVolunteeringId() == $volunteeringId) {
        $networks[] = $participantVolunteering->getNetwork();
      }
    }

    return $networks;
  }

  /**
   * Returns all users with the given volunteering type in the given network ids
   *
   * @param int|VolunteeringApi $volunteering The volunteering type (id)
   * @param int[]|NetworkApi[] $networks The networks (ids) in question
   *
   * @return UserApi[] All matching users
   */
  public static function getAllUsersWithTypeForNetworks($volunteering, array $networks) {
    if ($volunteering instanceof VolunteeringApi) {
      $volunteering = $volunteering->getId();
    }

    $networkIds = array();
    foreach ($networks as $network) {
      if ($network instanceof NetworkApi) {
        $networkIds[] = $network->getId();
      }
      else {
        $networkIds[] = $network;
      }
    }

    $participantVolunteering =
      CRUDApiMisc::getAllWherePropertyEquals(new ParticipantVolunteeringApi(), 'volunteering_id', $volunteering)
        ->getResults();

    $results = array();
    foreach ($participantVolunteering as $participantVolunteer) {
      if (in_array($participantVolunteer->getNetworkId(), $networkIds)) {
        $results[$participantVolunteer->getNetworkId()][] = $participantVolunteer->getUser();
      }
    }

    return $results;
  }

  /**
   * The type of volunteering id this participant signed up for
   *
   * @return int The volunteering id
   */
  public function getVolunteeringId() {
    return $this->volunteering_id;
  }

  /**
   * Returns the network for which the participant volunteerd
   *
   * @return NetworkApi The network
   */
  public function getNetwork() {
    if (!$this->networkInstance) {
      $this->networkInstance = NetworkApi::createNewInstance($this->network);
    }

    return $this->networkInstance;
  }

  /**
   * Set the network for which the volunteering holds
   *
   * @param int|NetworkApi $network The network (id)
   */
  public function setNetwork($network) {
    if ($network instanceof NetworkApi) {
      $network = $network->getId();
    }

    $this->network = NULL;
    $this->networkInstance = NULL;
    $this->network_id = $network;
    $this->toSave['network.id'] = $network;
  }

  /**
   * Returns the network id for which the participant volunteerd
   *
   * @return int The network id
   */
  public function getNetworkId() {
    return $this->network_id;
  }

  /**
   * Returns the participant date id of the participant that volunteerd
   *
   * @return int The participant id
   */
  public function getParticipantDateId() {
    return $this->participantDate_id;
  }

  /**
   * The participant details for this volunteering
   *
   * @return ParticipantDateAPI The participant
   */
  public function getParticipantDate() {
    if (!$this->participantDateInstance) {
      $this->participantDateInstance = ParticipantDateApi::createNewInstance($this->participantDate);
    }

    return $this->participantDateInstance;
  }

  /**
   * Set the participant that made the volunteering offer
   *
   * @param int|ParticipantDateApi $participantDate The participant (id)
   */
  public function setParticipantDate($participantDate) {
    if ($participantDate instanceof ParticipantDateApi) {
      $participantDate = $participantDate->getId();
    }

    $this->participantDate = NULL;
    $this->participantDateInstance = NULL;
    $this->participantDate_user = NULL;
    $this->userInstance = NULL;
    $this->participantDate_id = $participantDate;
    $this->toSave['participantDate.id'] = $participantDate;
  }

  /**
   * The type of volunteering this participant signed up for
   *
   * @return VolunteeringApi The volunteering type
   */
  public function getVolunteering() {
    if (!$this->volunteeringInstance) {
      $this->volunteeringInstance = VolunteeringApi::createNewInstance($this->volunteering);
    }

    return $this->volunteeringInstance;
  }

  /**
   * Set the volunteering type that was offered
   *
   * @param int|VolunteeringApi $volunteering The volunteering (id)
   */
  public function setVolunteering($volunteering) {
    if ($volunteering instanceof VolunteeringApi) {
      $volunteering = $volunteering->getId();
    }

    $this->volunteering = NULL;
    $this->volunteeringInstance = NULL;
    $this->volunteering_id = $volunteering;
    $this->toSave['volunteering.id'] = $volunteering;
  }

  /**
   * The user details for this volunteering
   *
   * @return UserAPI The user
   */
  public function getUser() {
    if (!$this->userInstance) {
      $this->userInstance = UserApi::createNewInstance($this->participantDate_user);
    }

    return $this->userInstance;
  }

  /**
   * Compare two participant volunteering by their name, by last name, then by first name
   *
   * @param ParticipantVolunteeringApi $instance Compare this instance with the given instance
   *
   * @return int &lt; 0 if <i>$instA</i> is less than
   * <i>$instB</i>; &gt; 0 if <i>$instA</i>
   * is greater than <i>$instB</i>, and 0 if they are
   * equal.
   */
  protected function compareWith($instance) {
    return $this->getUser()->compareWith($instance->getUser());
  }
}