<?php
namespace Drupal\iish_conference\API;

use Drupal\iish_conference\API\Domain\UserApi;

/**
 * API that allows emails to be send
 */
class SendEmailApi {
  private static $apiName = 'sendEmail';
  private $client;

  public function __construct() {
    $this->client = new ConferenceApiClient();
  }

  /**
   * Sends an email that tells the user how to make a bank transfer
   *
   * @param int|UserApi $userId The user (id) to whom the email is addressed
   * @param int $orderId The payment number / order id
   *
   * @return bool Returns whether the action was successful or not
   */
  public function sendBankTransferEmail($userId, $orderId) {
    return $this->sendEmail(SettingsApi::BANK_TRANSFER_EMAIL_TEMPLATE_ID, $userId, array(
      'orderId' => $orderId
    ));
  }

  /**
   * Sends an email that informs the user his payment has been accepted
   *
   * @param int|UserApi $userId The user (id) to whom the email is addressed
   * @param int $orderId The payment number / order id
   *
   * @return bool Returns whether the action was successful or not
   */
  public function sendPaymentAcceptedEmail($userId, $orderId) {
    return $this->sendEmail(SettingsApi::PAYMENT_ACCEPTED_EMAIL_TEMPLATE_ID, $userId, array(
      'orderId' => $orderId
    ));
  }

  /**
   * Sends an email that informs the user his payment on site request has been received
   *
   * @param int|UserApi $userId The user (id) to whom the email is addressed
   * @param int $orderId The payment number / order id
   *
   * @return bool Returns whether the action was successful or not
   */
  public function sendPaymentOnSiteEmail($userId, $orderId) {
    return $this->sendEmail(SettingsApi::PAYMENT_ON_SITE_EMAIL_TEMPLATE_ID, $userId, array(
      'orderId' => $orderId
    ));
  }

  /**
   * Sends an emails that details the pre registration he/she just finished
   *
   * @param int|UserApi $userId The user (id) to whom the email is addressed
   *
   * @return bool Returns whether the action was successful or not
   */
  public function sendPreRegistrationFinishedEmail($userId) {
    return $this->sendEmail(SettingsApi::PRE_REGISTRATION_EMAIL_TEMPLATE_ID, $userId, array());
  }

  /**
   * Allows emails to be send
   *
   * @param string $settingPropertyName The name of the setting property that hols the specific email template to use
   * @param int|UserApi $userId The user (id) to whom the email is addressed
   * @param array $props The properties to include in the email
   *
   * @return bool Returns whether the action was successful or not
   */
  private function sendEmail($settingPropertyName, $userId, array $props) {
    if ($userId instanceof UserApi) {
      $userId = $userId->getId();
    }

    $response = $this->client->get(self::$apiName, array_merge($props, array(
      'settingPropertyName' => $settingPropertyName,
      'userId' => $userId,
    )));

    return ($response !== NULL) ? $response['success'] : FALSE;
  }
} 