<?php
namespace Drupal\iish_conference_finalregistration\Controller;

use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Controller\ControllerBase;

use Drupal\iish_conference\ConferenceMisc;
use Drupal\iish_conference\Markup\ConferenceHTML;

use Drupal\iish_conference\API\CRUDApiMisc;
use Drupal\iish_conference\API\SettingsApi;
use Drupal\iish_conference\API\SendEmailApi;
use Drupal\iish_conference\API\LoggedInUserDetails;
use Drupal\iish_conference\API\CachedConferenceApi;

use Drupal\iish_conference\API\Domain\ParticipantDateApi;
use Drupal\iish_conference\API\Domain\ParticipantStateApi;

use Drupal\iish_conference\ConferenceTrait;
use Drupal\iish_conference_finalregistration\API\PayWayMessage;
use Drupal\iish_conference_finalregistration\API\RefreshOrderApi;

use Symfony\Component\HttpFoundation\Response;

/**
 * The controller for the final registration.
 */
class FinalRegistrationController extends ControllerBase {
  use ConferenceTrait;

  /**
   * Gives the user information about their bank transfer order.
   *
   * @return string|array|Response The render array or an empty page.
   */
  public function bankTransfer() {
    $this->redirectIfNotLoggedIn();
    $finalRegistrationLink = Link::fromTextAndUrl(iish_t('Click here'),
      Url::fromRoute('iish_conference_finalregistration.form'));

      // TODO: it should also work with participants with 'not finished pre-registrations'
	  if ( LoggedInUserDetails::isAParticipant() && LoggedInUserDetails::getParticipant()->getPaymentId()) {
//	  if ( ( LoggedInUserDetails::isAParticipant() || LoggedInUserDetails::isAParticipantWithoutConfirmation() ) && LoggedInUserDetails::getParticipant()->getPaymentId()) {
      $participant = LoggedInUserDetails::getParticipant();
      $orderDetails = new PayWayMessage(array('orderid' => $participant->getPaymentId()));
      $order = $orderDetails->send('orderDetails');

      if (!empty($order)) {
        if ($order->get('payed') == 1) {
          drupal_set_message(iish_t('You have already completed your final registration and payment.'), 'status');

          return array();
        }
        else if ($order->get('paymentmethod') == 1) {
          $bankTransferInfo = SettingsApi::getSetting(SettingsApi::BANK_TRANSFER_INFO);
          $amount = ConferenceMisc::getReadableAmount($order->get('amount'), TRUE);
          $finalDate = date('l j F Y', $participant->getBankTransferFinalDate($order->getDateTime('createdat')));
          $fullName = LoggedInUserDetails::getUser()->getFullName();

          $bankTransferInfo = str_replace('[PaymentNumber]', $order->get('orderid'), $bankTransferInfo);
          $bankTransferInfo = str_replace('[PaymentAmount]', $amount, $bankTransferInfo);
          $bankTransferInfo = str_replace('[PaymentDescription]', $order->get('com'), $bankTransferInfo);
          $bankTransferInfo = str_replace('[PaymentFinalDate]', $finalDate, $bankTransferInfo);
          $bankTransferInfo = str_replace('[NameParticipant]', $fullName, $bankTransferInfo);

          return array('#markup' => new ConferenceHTML($bankTransferInfo));
        }
        else if ($order->get('paymentmethod') == 2) {
          $cashPaymentInfo = SettingsApi::getSetting(SettingsApi::ON_SITE_PAYMENT_INFO);
          $amount = ConferenceMisc::getReadableAmount($order->get('amount'), TRUE);
          $fullName = LoggedInUserDetails::getUser()->getFullName();

          $cashPaymentInfo = str_replace('[PaymentNumber]', $order->get('orderid'), $cashPaymentInfo);
          $cashPaymentInfo = str_replace('[PaymentAmount]', $amount, $cashPaymentInfo);
          $cashPaymentInfo = str_replace('[PaymentDescription]', $order->get('com'), $cashPaymentInfo);
          $cashPaymentInfo = str_replace('[NameParticipant]', $fullName, $cashPaymentInfo);

          return array('#markup' => new ConferenceHTML($cashPaymentInfo));
        }
        else {
          drupal_set_message(iish_t('You have chosen an unknown payment method. @link to change your payment method.',
            array('@link' => $finalRegistrationLink->toString())), 'error');

          return array();
        }
      }

      drupal_set_message(iish_t('Currently it is not possible to obtain your payment information. ' .
        'Please try again later...'), 'error');

      return array();
    }

    drupal_set_message(iish_t('You have not finished the final registration. @link.',
      array('@link' => $finalRegistrationLink->toString())), 'error');

    return array();
  }

  /**
   * Called when a payment was accepted.
   *
   * @return array The message.
   */
  public function acceptPayment() {
    $paymentResponse = new PayWayMessage(\Drupal::request()->query->all());

    // 'POST' indicates that it is a one time response after the payment has been made, in our case, to send an email
    if ($paymentResponse->isSignValid() && $paymentResponse->get('POST')) {
      $userId = $paymentResponse->get('userid');
      $orderId = $paymentResponse->get('orderid');

      // TODO: Deprecated
      if ($userId !== NULL) {
        $participant = $this->getParticipant($userId);
        $participant->setPaymentId($orderId);
        $participant->save();
      }

      // Also make sure the CMS side is aware of the update of this order
      $refreshOrderApi = new RefreshOrderApi();
      $refreshOrderApi->refreshOrder($orderId);

      // Send an email to inform the user his payment has been accepted
      $sendEmailApi = new SendEmailApi();
      $sendEmailApi->sendPaymentAcceptedEmail($userId, $orderId);
    }

    return array('#markup' => iish_t('Thank you. The procedure has been completed successfully! ' .
      'Within a few minutes you will receive an email from us confirming your \'final registration and payment\' ' .
      'and you will receive a second email from the payment provider confirming your payment.'));
  }

  /**
   * Called when a payment was declined.
   *
   ** @return array The message.
   */
  public function declinePayment() {
    return array('#markup' => iish_t('Unfortunately, your payment has been declined. Please try to finish your final registration ' .
      'at a later moment or try a different payment method.'));
  }

  /**
   * Called when a payment result is uncertain.
   *
   * @return array The message.
   */
  public function exceptionPayment() {
    return array('#markup' => iish_t('Unfortunately, your payment result is uncertain at the moment.') . '<br />' .
    iish_t('Please contact @email to request information on your payment transaction.',
      array(
        '@email' => ConferenceMisc::emailLink(
          SettingsApi::getSetting(SettingsApi::DEFAULT_ORGANISATION_EMAIL)
        )->toString()
      )
    ));
  }

  /**
   * The title.
   * @return string The title.
   */
  public function getTitle() {
    try {
      return iish_t('Final registration and payment for the') .
      ' ' . CachedConferenceApi::getEventDate();
    }
    catch (\Exception $exception) {
      return t('Final registration and payment');
    }
  }

  /**
   * Returns the participant for the given user id.
   *
   * @param int $userId The user id.
   * @return ParticipantDateApi The participant.
   */
  private static function getParticipant($userId) {
    return CRUDApiMisc::getFirstWherePropertyEquals(new ParticipantDateApi(), 'user_id', $userId);
  }
}
