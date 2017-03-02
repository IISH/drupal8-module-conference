<?php
namespace Drupal\iish_conference_emails\Controller;

use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Form\FormState;
use Drupal\Core\Controller\ControllerBase;

use Drupal\iish_conference\API\SettingsApi;
use Drupal\iish_conference\API\CRUDApiClient;
use Drupal\iish_conference\API\ApiCriteriaBuilder;
use Drupal\iish_conference\API\LoggedInUserDetails;
use Drupal\iish_conference\API\Domain\SentEmailApi;

use Drupal\iish_conference\ConferenceTrait;
use Drupal\iish_conference\Markup\ConferenceHTML;
use Drupal\iish_conference_emails\Form\ResendEmailForm;

use Symfony\Component\HttpFoundation\Response;

/**
 * The controller for the emails.
 */
class EmailsController extends ControllerBase {
  use ConferenceTrait;

  /**
   * Requests the emails for the current page and displays them.
   *
   * @return array|Response The render array.
   */
  public function listEmails() {
    $this->redirectIfNotLoggedIn();
    $maxTries = intval(SettingsApi::getSetting(SettingsApi::EMAIL_MAX_NUM_TRIES));

    $props = new ApiCriteriaBuilder();
    $emailsNotSent = SentEmailApi::getListWithCriteria(
      $props
        ->eq('user_id', LoggedInUserDetails::getId())
        ->eq('dateTimeSent', NULL)
        ->sort('dateTimeCreated', 'desc')
        ->get()
    )->getResults();

    $props = new ApiCriteriaBuilder();
    $emailsSent = SentEmailApi::getListWithCriteria(
      $props
        ->eq('user_id', LoggedInUserDetails::getId())
        ->ge('dateTimeSent', strtotime('-18 month'))
        ->sort('dateTimeSent', 'desc')
        ->get()
    )->getResults();

    // Now also sort on subject
    SentEmailApi::setSortOnCreated(TRUE);
    CRUDApiClient::sort($emailsNotSent);

    SentEmailApi::setSortOnCreated(FALSE);
    CRUDApiClient::sort($emailsSent);

    $rowsNotSent = array();
    foreach ($emailsNotSent as $email) {
      $rowsNotSent[] = array(
        Link::fromTextAndUrl($email->getSubject(),
          Url::fromRoute('iish_conference_emails.email',
            array('sent_email' => $email->getId()))),

        (is_null($email->getDateTimeCreated()))
          ? NULL
          : date('j F Y H:i:s', $email->getDateTimeCreated()),

        ($email->getNumTries() >= $maxTries)
          ? '<span class="eca_warning">' . iish_t('Sending failed') . '</span>'
          : iish_t('Not sent yet'),
      );
    }

    $rowsSent = array();
    foreach ($emailsSent as $email) {
      $rowsSent[] = array(
        Link::fromTextAndUrl($email->getSubject(),
          Url::fromRoute('iish_conference_emails.email',
            array('sent_email' => $email->getId()))),

        (is_null($email->getDateTimeSent()))
          ? NULL
          : date('j F Y H:i:s', $email->getDateTimeSent()),
      );
    }

    return array(
      $this->backToPersonalPageLink(),

      array(
        '#theme' => 'iish_conference_container',
        '#fields' => array(
          array('header' => iish_t('Emails in queue, waiting to be sent')),
          array(
            '#type' => 'table',
            "#header" => array(
              iish_t('Email subject'),
              iish_t('Date/time created'),
              iish_t('Sending status'),
            ),
            '#sticky' => TRUE,
            '#empty' => iish_t('No emails found!'),
            '#rows' => $rowsNotSent,
          )
        )
      ),

      array(
        '#theme' => 'iish_conference_container',
        '#fields' => array(
          array('header' => iish_t('Sent emails')),
          array(
            '#type' => 'table',
            "#header" => array(
              iish_t('Email subject'),
              iish_t('Date/time sent'),
            ),
            '#sticky' => TRUE,
            '#empty' => iish_t('No emails found!'),
            '#rows' => $rowsSent,
          )
        )
      ),
    );
  }

  /**
   * The main page for viewing the details of an email message and resending them.
   *
   * @param SentEmailApi $sent_email The email in question
   * @return array|Response The render array.
   */
  public function email($sent_email) {
    $this->redirectIfNotLoggedIn();

    if (empty($sent_email)) {
      drupal_set_message(iish_t('Unfortunately, this email does not seem to exist.'), 'error');
      return $this->redirect('iish_conference_emails.index');
    }

    if ($sent_email->getUserId() !== LoggedInUserDetails::getId()) {
      drupal_set_message(iish_t('You are only allowed to see emails sent to you.'), 'error');
      return $this->redirect('iish_conference_emails.index');
    }

    $form_state = new FormState();
    $form_state->set('email', $sent_email);
    $resendEmailForm = \Drupal::formBuilder()->buildForm(ResendEmailForm::class, $form_state);

    return array(
      array(
        '#markup' => '<div class="iishconference_container_inline bottommargin">'
          . Link::fromTextAndUrl('« ' . iish_t('Go back to your emails'), Url::fromRoute('iish_conference_emails.index'))->toString()
          . '&nbsp;'
          . Link::fromTextAndUrl('« ' . iish_t('Go back to your personal page'), Url::fromRoute('iish_conference_personalpage.index'))->toString()
          . '</div>'
      ),

      array(
        '#theme' => 'iish_conference_container',
        '#fields' => array(
          array(
            'label' => 'Original email sent on',
            'value' => (!is_null($sent_email->getDateTimeSent())
              ? $sent_email->getDateTimeSentFormatted('j F Y H:i:s')
              : iish_t('Not sent yet')),
          ),

          array(
            'label' => 'Copies of this email sent on',
            'value' => (!is_null($sent_email->getDateTimesSentCopy())
              ? implode(', ', $sent_email->getDateTimesSentCopyFormatted('j F Y H:i:s'))
              : iish_t('No copies sent yet')),
          ),

          array(
            'label' => 'Email from',
            'value' => $sent_email->getFromName() . ' ( ' . $sent_email->getFromEmail() . ' )',
          ),

          array(
            'label' => 'Email subject',
            'value' => $sent_email->getSubject(),
          ),

          array(
            'label' => 'Email message',
            'value' => $sent_email->getBody(),
            'newLine' => TRUE
          ),

          new ConferenceHTML('<br>', TRUE),

          $resendEmailForm
        ),
      ),
    );
  }
}