<?php
namespace Drupal\iish_conference_emails\API;

use Drupal\iish_conference\API\ConferenceApiClient;
use Drupal\iish_conference\API\Domain\SentEmailApi;

/**
 * API which allows for resending emails
 */
class ResendEmailApi {
  private $client;
  private static $apiName = 'resendEmail';

  public function __construct() {
    $this->client = new ConferenceApiClient();
  }

  /**
   * Resend the given email (id)
   *
   * @param int|SentEmailApi $emailId The email or email id to be resend
   *
   * @return bool Whether the action was successful
   */
  public function resendEmail($emailId) {
    if ($emailId instanceof SentEmailApi) {
      $emailId = $emailId->getId();
    }

    $response = $this->client->get(self::$apiName, array(
      'emailId' => $emailId,
    ));

    return ($response !== NULL) ? $response['success'] : FALSE;
  }
} 