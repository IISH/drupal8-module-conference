<?php
namespace Drupal\iish_conference_finalregistration\API;

use Drupal\iish_conference\API\ConferenceApiClient;

/**
 * API that makes sure specific orders are refreshed on the CMS side
 */
class RefreshOrderApi {
  private static $apiName = 'refreshOrder';
  
  private $client;

  public function __construct() {
    $this->client = new ConferenceApiClient();
  }

  /**
   * Refreshes the order with this specific order id
   *
   * @param int $orderId The order id of the order in question
   * @param int|null $participantId The id of the participant
   *
   * @return bool Whether the refresh was successful or not
   */
  public function refreshOrder($orderId, $participantId = NULL) {
    $response = $this->client->get(self::$apiName, array(
      'orderId' => $orderId,
      'participantId' => $participantId
    ));

    return ($response !== NULL) ? $response['success'] : FALSE;
  }
} 