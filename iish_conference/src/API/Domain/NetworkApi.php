<?php
namespace Drupal\iish_conference\API\Domain;

use Drupal\iish_conference\API\SettingsApi;
use Drupal\iish_conference\API\CRUDApiMisc;
use Drupal\iish_conference\API\CRUDApiClient;
use Drupal\iish_conference\API\ApiCriteriaBuilder;

/**
 * Holds a network obtained from the API
 */
class NetworkApi extends CRUDApiClient {
  protected $name;
  protected $comment;
  protected $longDescription;
  protected $url;
  protected $email;
  protected $showOnline;
  protected $chairs_chair_id;

  private $chairs;

  /**
   * Make sure we only obtain networks that can be shown online.
   * Allows the user to get a list with instances of this class based on a list with criteria
   *
   * @param array $properties The criteria
   *
   * @return NetworkApi|null
   */
  public static function getListWithCriteria(array $properties) {
    $prop = new ApiCriteriaBuilder();
    $properties = array_merge($prop->eq('showOnline', TRUE)
      ->get(), $properties);

    return parent::getListWithCriteria($properties);
  }

  /**
   * For the given list of networks, filter out the networks in which the given user is not a chair
   *
   * @param NetworkApi[] $networks All the networks to filter on
   * @param UserApi $chair The user/chair in question
   *
   * @return NetworkApi[] The networks of the given chair
   */
  public static function getOnlyNetworksOfChair($networks, $chair) {
    foreach ($networks as $i => $network) {
      if (!in_array($chair->getId(), $network->chairs_chair_id, FALSE)) {
        unset($networks[$i]);
      }
    }

    return array_values($networks);
  }

  /**
   * Returns the network name of the current conference
   *
   * @param bool $singular Whether the singular or plural form should be returned
   * @param bool $lowercase Whether it should be all lowercase
   *
   * @return string The network name
   */
  public static function getNetworkName($singular = TRUE, $lowercase = FALSE) {
    if ($singular) {
      $networkName = SettingsApi::getSetting(SettingsApi::NETWORK_NAME_SINGULAR);
    }
    else {
      $networkName = SettingsApi::getSetting(SettingsApi::NETWORK_NAME_PLURAL);
    }

    if ($lowercase) {
      $networkName = strtolower($networkName);
    }

    return $networkName;
  }

  /**
   * Returns a list with the ids of all the chairs of this network
   *
   * @return int[] A list of chair ids
   */
  public function getChairsId() {
    return $this->chairs_chair_id;
  }

  /**
   * Returns the comment (short description) of this network
   *
   * @return string|null The comment
   */
  public function getComment() {
    return $this->comment;
  }

  /**
   * Returns the long description of this network
   *
   * @return string|null The long description
   */
  public function getLongDescription() {
    return $this->longDescription;
  }

  /**
   * Returns the email address for correspondence with this network
   *
   * @return string The email address for correspondence with this network
   */
  public function getEmail() {
    return $this->email;
  }

  /**
   * Returns the URL for this network
   *
   * @return string The URL for this network
   */
  public function getUrl() {
    return $this->url;
  }

  /**
   * Returns a list with all the chairs of this network
   *
   * @return UserApi[] A list of users who are chairs
   */
  public function getChairs() {
    if (!$this->chairs) {
      $this->chairs = array();
      foreach ($this->chairs_chair_id as $id) {
        $this->chairs[] = CRUDApiMisc::getById(new UserApi(), $id);
      }
    }

    return $this->chairs;
  }

  public function __toString() {
    return $this->getName();
  }

  /**
   * Returns the name of this network
   *
   * @return string The name of this network
   */
  public function getName() {
    return $this->name;
  }

  /**
   * Compare two networks, by name
   *
   * @param NetworkApi $instance Compare this instance with the given instance
   *
   * @return int &lt; 0 if <i>$instA</i> is less than
   * <i>$instB</i>; &gt; 0 if <i>$instA</i>
   * is greater than <i>$instB</i>, and 0 if they are
   * equal.
   */
  protected function compareWith($instance) {
    return strcmp(strtolower($this->getName()), strtolower($instance->getName()));
  }
} 