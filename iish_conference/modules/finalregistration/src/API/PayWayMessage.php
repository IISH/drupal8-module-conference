<?php
namespace Drupal\iish_conference_finalregistration\API;

use Drupal\Component\Serialization\Json;
use Drupal\iish_conference\API\SettingsApi;

/**
 * Represents messages from and to PayWay
 */
class PayWayMessage {
  const ORDER_NOT_PAYED = 0;
  const ORDER_PAYED = 1;
  const ORDER_REFUND_OGONE = 2;
  const ORDER_REFUND_BANK = 3;

  const ORDER_OGONE_PAYMENT = 0;
  const ORDER_BANK_PAYMENT = 1;
  const ORDER_CASH_PAYMENT = 2;

  private $message;

  /**
   * Creates a new message from or to PayWay
   *
   * @param array $message The message parameters and their keys
   *
   * @return PayWayMessage
   */
  public function __construct(array $message = array()) {
    $this->message = array();

    foreach ($message as $parameter => $value) {
      $this->add($parameter, $value);
    }
  }

  /**
   * Adds a new parameter/value pair to the message
   *
   * @param string $parameter The parameter, as defined by PayWay
   * @param mixed $value The value for this parameter
   *
   * @return PayWayMessage
   */
  public function add($parameter, $value) {
    $parameter = trim(strtoupper($parameter));

    $paramIsOk = !empty($parameter);
    $valueIsOk = is_string($value) ? strlen(trim($value)) > 0 : !is_null($value);

    if ($paramIsOk && $valueIsOk) {
      $this->message[$parameter] = $value;
    }

    return $this;
  }

  /**
   * Returns the date/time value for the given parameter
   *
   * @param string $parameter The parameter in question
   *
   * @return int|null The time if it can be parsed
   */
  public function getDateTime($parameter) {
    $parameter = trim(strtoupper($parameter));

    if (array_key_exists($parameter, $this->message)) {
      return strtotime($this->message[$parameter]);
    }
    else {
      return NULL;
    }
  }

  /**
   * Sends this message to PayWay
   *
   * @param string $apiName The name of the PayWay API to send the message to
   *
   * @return bool|PayWayMessage|void Returns a new PayWayMessage with the response, unless there is an error.
   * In that case, FALSE is returned. For payments, the user is redirected to the payment page
   */
  public function send($apiName) {
    $this->addProject()->sign();

    // If a payment has to be made, redirect the user to payment page
    if ($apiName == 'payment') {
      $this->redirectToPayWay();
    }
    else {
      $client = \Drupal::httpClient();

      $request = $client->createRequest('POST', SettingsApi::getSetting(SettingsApi::PAYWAY_ADDRESS) . $apiName);
      $request->addHeader('Content-Type', 'text/json');
      $request->setBody(Json::encode($this->message));

      $response = $request->send();
      if ($response->getStatusCode() === 200) {
        $message = new PayWayMessage(Json::decode($response->getBody()));
        if ($message->isSignValid()) {
          return $message;
        }
      }
    }

    return FALSE;
  }

  /**
   * Checks to see if the message signed by PayWay is correct
   *
   * @return bool Returns TRUE if the signature is valid
   */
  public function isSignValid() {
    $successExists = array_key_exists('SUCCESS', $this->message);

    if (!$successExists || ($successExists && $this->get('success'))) {
      $curSign = $this->get('shasign');
      $this->sign(FALSE);

      return ($curSign == $this->get('shasign'));
    }

    return FALSE;
  }

  /**
   * Returns the value for the given parameter
   *
   * @param string $parameter The parameter in question
   *
   * @return mixed The value of the given parameter in this message
   */
  public function get($parameter) {
    $parameter = trim(strtoupper($parameter));

    if (array_key_exists($parameter, $this->message)) {
      return $this->message[$parameter];
    }
    else {
      return NULL;
    }
  }

  /**
   * Cleans up the message and signs the message
   *
   * @param bool $in TRUE if send to PayWay, FALSE if coming from PayWay
   *
   * @return PayWayMessage
   */
  private function sign($in = TRUE) {
    $passPhrase = SettingsApi::getSetting(SettingsApi::PAYWAY_PASSPHRASE_IN);
    if (!$in) {
      $passPhrase = SettingsApi::getSetting(SettingsApi::PAYWAY_PASSPHRASE_OUT);
    }

    // Sort and cleanup the message
    ksort($this->message);
    unset($this->message['SHASIGN']);

    // Create the signature and add it to the message
    $messageConcatenated = array();
    foreach ($this->message as $parameter => $value) {
      // Boolean values are printed as '1' and '0', but should be printed as 'true' and 'false'.
      if (is_bool($value)) {
        $value = $value ? 'true' : 'false';
      }
      $messageConcatenated[] = $parameter . '=' . $value;
    }

    $toBeHashed = implode($passPhrase, $messageConcatenated) . $passPhrase;
    $this->add('shasign', sha1($toBeHashed));

    return $this;
  }

  /**
   * Adds the project name to the message
   *
   * @return PayWayMessage
   */
  private function addProject() {
    $this->add('project', SettingsApi::getSetting(SettingsApi::PAYWAY_PROJECT));

    return $this;
  }

  /**
   * Redirects the user to PayWay payment page with this message
   */
  private function redirectToPayWay() {
    header('Location: ' . SettingsApi::getSetting(SettingsApi::PAYWAY_ADDRESS) . 'payment?' .
      http_build_query($this->message, NULL, '&'));
    die();
  }
} 