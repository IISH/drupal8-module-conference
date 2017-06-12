<?php
namespace Drupal\iish_conference_finalregistration\Form\Page;

use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use Drupal\iish_conference\API\SettingsApi;
use Drupal\iish_conference\API\SendEmailApi;
use Drupal\iish_conference\API\CachedConferenceApi;
use Drupal\iish_conference\API\LoggedInUserDetails;

use Drupal\iish_conference_finalregistration\API\PayWayMessage;
use Drupal\iish_conference_finalregistration\API\RefreshOrderApi;

/**
 * The final registration form overview page.
 */
class OverviewPage extends FormBase {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'conference_final_registration_overview';
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['back'] = array(
      '#type' => 'submit',
      '#name' => 'back',
      '#value' => iish_t('Previous step'),
    );

    $form['confirm'] = array(
      '#type' => 'submit',
      '#name' => 'confirm',
      '#value' => iish_t('Confirm'),
    );

    $form['payway'] = array(
      '#type' => 'submit',
      '#name' => 'payway',
      '#value' => iish_t('Make online payment'),
    );

    if (SettingsApi::getSetting(SettingsApi::BANK_TRANSFER_ALLOWED) == 1) {
      $form['bank_transfer'] = array(
        '#type' => 'submit',
        '#name' => 'bank_transfer',
        '#value' => iish_t('Make payment by bank transfer'),
      );
    }

    $form['on_site'] = array(
      '#type' => 'submit',
      '#name' => 'on_site',
      '#value' => iish_t('Pay on site'),
    );

    if (strlen(trim(SettingsApi::getSetting(SettingsApi::GENERAL_TERMS_CONDITIONS_LINK))) > 0) {
      $link = Link::fromTextAndUrl(iish_t('General terms and conditions'),
        Url::fromUri(SettingsApi::getSetting(SettingsApi::GENERAL_TERMS_CONDITIONS_LINK),
          array('arguments' => array('target' => '_blank'))));

      $form['terms_and_conditions'] = array(
        '#title' => iish_t('Check the box to accept the') . ' ' . $link->toString() . '.',
        '#type' => 'checkbox',
        '#default_value' => FALSE,
      );
    }

    return $form;
  }

  /**
   * Form validation handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ((strlen(trim(SettingsApi::getSetting(SettingsApi::GENERAL_TERMS_CONDITIONS_LINK))) > 0) &&
      ($form_state->getValue('terms_and_conditions') !== 1)
    ) {
      $form_state->setErrorByName('terms_and_conditions', iish_t('You have to accept the general terms and conditions.'));
    }
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $participant = LoggedInUserDetails::getParticipant();
    $user = LoggedInUserDetails::getUser();

    $paymentMethod = PayWayMessage::ORDER_OGONE_PAYMENT;
    if ((SettingsApi::getSetting(SettingsApi::BANK_TRANSFER_ALLOWED) == 1) &&
      ($form_state->getTriggeringElement()['#name'] === 'bank_transfer')
    ) {
      $paymentMethod = PayWayMessage::ORDER_BANK_PAYMENT;
    }
    if ($form_state->getTriggeringElement()['#name'] === 'on_site') {
      $paymentMethod = PayWayMessage::ORDER_CASH_PAYMENT;
    }

    $totalAmount = ($paymentMethod === PayWayMessage::ORDER_CASH_PAYMENT)
      ? $participant->getTotalAmountPaymentOnSite()
      : $participant->getTotalAmount();

    // Create the order, if successful, redirect user to payment page
    $createOrder = new PayWayMessage(array(
      'amount' => intval($totalAmount * 100),
      'currency' => 'EUR',
      'language' => 'en_US',
      'cn' => $user->getFullName(),
      'email' => $user->getEmail(),
      'owneraddress' => NULL,
      'ownerzip' => NULL,
      'ownertown' => $user->getCity(),
      'ownercty' => ($user->getCountry() !== NULL) ? $user->getCountry()->getIsoCode() : NULL,
      'ownertelno' => $user->getPhone(),
      'com' => CachedConferenceApi::getEventDate() . ' ' . iish_t('payment'),
      'paymentmethod' => $paymentMethod,
    ));
    $order = $createOrder->send('createOrder');

    // If creating a new order is successful, redirect to PayWay or to bank transfer information or just succeed?
    if (!empty($order) && $order->get('success')) {
      $orderId = $order->get('orderid');

      // Save order id
      $participant->setPaymentId($orderId);
      $participant->save();

      // Also make sure the CMS has a copy of the order
      $refreshOrderApi = new RefreshOrderApi();
      $refreshOrderApi->refreshOrder($orderId, $participant->getId());

      // If no payment is necessary now, just confirm and send an email
      if (($totalAmount == 0) || ($paymentMethod === PayWayMessage::ORDER_CASH_PAYMENT)) {
        if ($totalAmount == 0) {
          $sendEmailApi = new SendEmailApi();
          $sendEmailApi->sendPaymentAcceptedEmail($participant->getUserId(), $orderId);
        }

        if ($paymentMethod === PayWayMessage::ORDER_CASH_PAYMENT) {
          $sendEmailApi = new SendEmailApi();
          $sendEmailApi->sendPaymentOnSiteEmail($participant->getUserId(), $orderId);
        }

        $form_state->setRedirect('iish_conference_finalregistration.accept');
      }
      else {
        if ($paymentMethod === PayWayMessage::ORDER_OGONE_PAYMENT) {
          $payment = new PayWayMessage(array('orderid' => $orderId));
          $payment->send('payment');
        }
        else {
          $sendEmailApi = new SendEmailApi();
          $sendEmailApi->sendBankTransferEmail($participant->getUserId(), $orderId);

          $form_state->setRedirect('iish_conference_finalregistration.bank_transfer');
        }
      }
    }
    else {
      drupal_set_message(iish_t('Currently it is not possible to proceed to create a new order. Please try again later...'),
        'error');
    }
  }
}
