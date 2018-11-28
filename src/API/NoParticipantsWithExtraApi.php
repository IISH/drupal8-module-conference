<?php
namespace Drupal\iish_conference\API;

use Drupal\iish_conference\API\Domain\ExtraApi;

/**
 * API which retrieves the number of participants with a certain extra
 */
class NoParticipantsWithExtraApi {
  private static $apiName = 'noParticipantsWithExtra';
  private $client;

  public function __construct() {
    $this->client = new ConferenceApiClient();
  }

  /**
   * Retrieve the total number of participants for the given extra instance
   *
   * @param ExtraApi|int $extra The extra (id) of which we want the total number of participants
   *
   * @return int|null The number of participants, or null on failure
   */
  public function getNoParticipantsWithExtra($extra) {
    if ($extra instanceof ExtraApi) {
      $extra = $extra->getId();
    }

    $response = $this->client->get(self::$apiName, array(
      'extraId' => $extra,
    ));

    return (($response !== NULL) && $response['success']) ? $response['no_participants'] : NULL;
  }
}