<?php
namespace Drupal\iish_conference_personalpage\Controller;

use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Form\FormState;
use Drupal\Core\Controller\ControllerBase;

use Drupal\iish_conference\API\SettingsApi;
use Drupal\iish_conference\API\CRUDApiMisc;
use Drupal\iish_conference\API\CRUDApiClient;
use Drupal\iish_conference\API\AccessTokenApi;
use Drupal\iish_conference\API\LoggedInUserDetails;
use Drupal\iish_conference\API\CachedConferenceApi;

use Drupal\iish_conference\API\Domain\UserApi;
use Drupal\iish_conference\API\Domain\PaperApi;
use Drupal\iish_conference\API\Domain\SessionApi;
use Drupal\iish_conference\API\Domain\VolunteeringApi;
use Drupal\iish_conference\API\Domain\ParticipantDateApi;
use Drupal\iish_conference\API\Domain\ParticipantStateApi;
use Drupal\iish_conference\API\Domain\SessionParticipantApi;
use Drupal\iish_conference\API\Domain\SessionRoomDateTimeApi;
use Drupal\iish_conference\API\Domain\ParticipantVolunteeringApi;

use Drupal\iish_conference\ConferenceMisc;
use Drupal\iish_conference\ConferenceTrait;
use Drupal\iish_conference\Markup\ConferenceHTML;

use Drupal\iish_conference_personalpage\Form\DeletePaperForm;
use Drupal\iish_conference_finalregistration\API\PayWayMessage;

use Symfony\Component\HttpFoundation\Response;

/**
 * The controller for the personal page.
 */
class PersonalPageController extends ControllerBase {
  use ConferenceTrait;

  const UPLOAD_PAPER_ERROR_NONE = 0;
  const UPLOAD_PAPER_ERROR_ID_NOT_FOUND = 1;
  const UPLOAD_PAPER_ERROR_USER_NOT_ALLOWED = 2;
  const UPLOAD_PAPER_ERROR_EMPTY_FILE = 3;
  const UPLOAD_PAPER_ERROR_LARGE_FILE = 4;
  const UPLOAD_PAPER_ERROR_EXT_NOT_ALLOWED = 5;
  const UPLOAD_PAPER_ERROR_OTHER = 6;

  /**
   * Renders the personal page.
   *
   * @return array|Response Render array.
   */
  public function index() {
    if (($response = $this->redirectIfNotLoggedIn()) !== FALSE) {
      return $response;
    }

    $userDetails = LoggedInUserDetails::getUser();
    $participantDateDetails = LoggedInUserDetails::getParticipant();

    $renderArray = array();
    $this->setPersonalInfo($renderArray, $userDetails, $participantDateDetails);
    $this->setRegistrationInfo($renderArray, $userDetails, $participantDateDetails);
    $this->setSessionsInfo($renderArray, $userDetails, $participantDateDetails);
    $this->setPapersInfo($renderArray, $userDetails, $participantDateDetails);
    $this->setChairDiscussantInfo($renderArray, $participantDateDetails);
    $this->setLanguageInfo($renderArray, $participantDateDetails);
    $this->setLinks($renderArray, $participantDateDetails);
    $this->setLinksNetwork($renderArray, $participantDateDetails);

    return $renderArray;
  }

  /**
   * Allows users to upload their paper.
   *
   * @param PaperApi $paper The paper.
   *
   * @return array|Response Render array.
   */
  public function uploadPaper($paper) {
    if (($response = $this->redirectIfNotLoggedIn()) !== FALSE) {
      return $response;
    }

    if (empty($paper)) {
      drupal_set_message(iish_t('Unfortunately, this paper does not seem to exist.'), 'error');
      $this->redirectToPersonalPage();
    }

    if ($paper->getUserId() !== LoggedInUserDetails::getId()) {
      drupal_set_message(iish_t('You are only allowed to upload a paper for your own papers.'), 'error');
      $this->redirectToPersonalPage();
    }

    $paperDownloadLink = NULL;
    if (($paper->getFileSize() !== NULL) && ($paper->getFileSize() > 0)) {
      $paperDownloadLink = Link::fromTextAndUrl($paper->getFileName(),
        Url::fromUri($paper->getDownloadURL()))->toString();
    }

    $backLink = Link::fromTextAndUrl('« ' . iish_t('Go back to your personal page'),
      Url::fromRoute('iish_conference_personalpage.index'))->toString();

    $accessTokenApi = new AccessTokenApi();
    $token = $accessTokenApi->accessToken(LoggedInUserDetails::getId());

    $config = \Drupal::config('iish_conference.settings');
    $url = $config->get('conference_base_url') . $config->get('conference_event_code') . '/' .
      $config->get('conference_date_code') . '/' . 'userApi/uploadPaper?access_token=' . $token;

    $maxSize = SettingsApi::getSetting(SettingsApi::MAX_UPLOAD_SIZE_PAPER);
    $allowedExtensions = SettingsApi::getSetting(SettingsApi::ALLOWED_PAPER_EXTENSIONS);

    $form_state = new FormState();
    $form_state->set('paper', $paper);
    $deleteForm = \Drupal::formBuilder()->buildForm(DeletePaperForm::class, $form_state);

    if ($error = \Drupal::request()->query->get('e') !== NULL) {
      switch ($error) {
        case self::UPLOAD_PAPER_ERROR_NONE:
          drupal_set_message(iish_t('Your paper has been successfully uploaded!'), 'status');
          break;
        case self::UPLOAD_PAPER_ERROR_ID_NOT_FOUND:
          drupal_set_message(iish_t('Your paper could not be found!'), 'error');
          break;
        case self::UPLOAD_PAPER_ERROR_USER_NOT_ALLOWED:
          drupal_set_message(iish_t('You are not allowed to upload your paper!'), 'error');
          break;
        case self::UPLOAD_PAPER_ERROR_EMPTY_FILE:
          drupal_set_message(iish_t('You have not uploaded a file!'), 'error');
          break;
        case self::UPLOAD_PAPER_ERROR_LARGE_FILE:
          drupal_set_message(iish_t('The file you uploaded is too large! The maximum size is @maxSize!',
            array('@maxSize' => ConferenceMisc::getReadableFileSize($maxSize))), 'error');
          break;
        case self::UPLOAD_PAPER_ERROR_EXT_NOT_ALLOWED:
          drupal_set_message(iish_t('You can only upload files with the following extensions: @extensions',
            array('@extensions' => $allowedExtensions)), 'error');
          break;
        case self::UPLOAD_PAPER_ERROR_OTHER:
        default:
          drupal_set_message(iish_t('An undefined error has occurred!'), 'error');
      }
    }

    return array(
      '#theme' => 'iish_conference_personalpage_upload_paper',
      '#paper' => $paper,
      '#paperDownloadLink' => $paperDownloadLink,
      '#actionUrl' => $url,
      '#maxSize' => ConferenceMisc::getReadableFileSize($maxSize),
      '#extensions' => $allowedExtensions,
      '#deleteForm' => $deleteForm,
      '#backUrl' => $_SERVER['REQUEST_URI'],
      '#backLink' => $backLink,
    );
  }

  /**
   * Creates the personal info container for the personal page
   *
   * @param array $renderArray The render array
   * @param UserApi $userDetails The user in question
   * @param ParticipantDateApi|null $participantDateDetails The user in question participant details, if registered
   */
  private function setPersonalInfo(array &$renderArray, $userDetails, $participantDateDetails) {
    $fields = array();

    $fields[] = array(
      'header' => iish_t('Personal Info'),
    );
    $fields[] = array(
      'label' => 'First name',
      'value' => $userDetails->getFirstName()
    );
    $fields[] = array(
      'label' => 'Last name',
      'value' => $userDetails->getLastName()
    );
    $fields[] = array(
      'label' => 'Gender',
      'value' => ConferenceMisc::getGender($userDetails->getGender())
    );
    $fields[] = array(
      'label' => 'Organisation',
      'value' => $userDetails->getOrganisation()
    );

    if (SettingsApi::getSetting(SettingsApi::SHOW_DEPARTMENT) == 1) {
      $fields[] = array(
        'label' => 'Department',
        'value' => $userDetails->getDepartment()
      );
    }

    if (SettingsApi::getSetting(SettingsApi::SHOW_EDUCATION) == 1) {
      $fields[] = array(
        'label' => 'Education',
        'value' => $userDetails->getEducation()
      );
    }

    $fields[] = array(
      'label' => 'E-mail',
      'value' => $userDetails->getEmail()
    );

    if (LoggedInUserDetails::isAParticipant() && (SettingsApi::getSetting(SettingsApi::SHOW_AGE_RANGE) == 1)) {
      $fields[] = array(
        'label' => 'Age',
        'value' => $participantDateDetails->getAgeRange()
      );
    }

    if (LoggedInUserDetails::isAParticipant() && (SettingsApi::getSetting(SettingsApi::SHOW_STUDENT) == 1)) {
      $fields[] = array(
        'label' => '(PhD) Student?',
        'value' => ConferenceMisc::getYesOrNo($participantDateDetails->getStudent())
      );
    }

    $fields[] = array(
      'label' => 'City',
      'value' => $userDetails->getCity()
    );
    $fields[] = array(
      'label' => 'Country',
      'value' => $userDetails->getCountry()->__toString()
    );
    $fields[] = array(
      'label' => 'Phone number',
      'value' => $userDetails->getPhone()
    );
    $fields[] = array(
      'label' => 'Mobile number',
      'value' => $userDetails->getMobile()
    );

    if (SettingsApi::getSetting(SettingsApi::SHOW_CV) == 1) {
      $fields[] = array(
        'label' => 'Curriculum Vitae',
        'value' => ConferenceMisc::getHTMLForLongText($userDetails->getCv()),
        'html' => TRUE,
        'newLine' => TRUE
      );
    }

    $renderArray[] = array(
      '#theme' => 'iish_conference_container',
      '#fields' => $fields
    );
  }

  /**
   * Creates the registration info content for the personal page
   *
   * @param array $renderArray The render array
   * @param UserApi $userDetails The user in question
   * @param ParticipantDateApi|null $participantDateDetails The user in question participant details, if registered
   */
  private function setRegistrationInfo(array &$renderArray, $userDetails, $participantDateDetails) {
    if (LoggedInUserDetails::isAParticipant()) {
      $isFinalRegistrationOpen = $this->moduleHandler()->moduleExists('iish_conference_finalregistration');

      $fields = array();

      $renderArray[] = array(
        '#markup' => '<div class="eca_remark heavy">'
          . iish_t('You have pre-registered for the @conference', array(
            '@conference' => CachedConferenceApi::getEventDate()->getLongNameAndYear()
          )) . '</div>'
      );

      $this->setPaymentStatus($renderArray, $participantDateDetails);

      if ($isFinalRegistrationOpen) {
        $fields[] = array(
          'label' => 'Currently selected fee',
          'value' => $participantDateDetails->getFeeState()
        );
      }

      $days = $userDetails->getDaysPresent();
      if ((count($days) > 0) && (SettingsApi::getSetting(SettingsApi::SHOW_DAYS) == 1)) {
        $fields[] = array(
          'label' => 'I will be present on the following days',
          'value' => array('#theme' => 'item_list', '#items' => $days),
          'newLine' => TRUE,
          'html' => TRUE,
        );
      }

      $extrasIds = $participantDateDetails->getExtrasId();
      foreach (CachedConferenceApi::getExtras() as $extra) {
        if (!$extra->isFinalRegistration() || $isFinalRegistrationOpen) {
          $userHasRegistered = (array_search($extra->getId(), $extrasIds) !== FALSE);
          $fields[] = array(
            'label' => $extra->getTitle(),
            'value' => ConferenceMisc::getYesOrNo($userHasRegistered)
          );
        }
      }

      if (SettingsApi::getSetting(SettingsApi::SHOW_ACCOMPANYING_PERSONS) == 1) {
        $accompanyingPersons = $participantDateDetails->getAccompanyingPersons();
        $fields[] = array(
          'label' => 'Accompanying person(s)',
          'value' => (count($accompanyingPersons) > 0) ? ConferenceMisc::getEnumSingleLine($accompanyingPersons) :
            iish_t('No accompanying person')
        );
      }

      $renderArray[] = array(
        '#theme' => 'iish_conference_container',
        '#fields' => $fields
      );
    }
    else {
      if ($this->moduleHandler()->moduleExists('iish_conference_preregistration')) {
        $preRegistrationLink = Link::fromTextAndUrl(iish_t('pre-registration form'),
          Url::fromRoute('iish_conference_preregistration.form'));

        if (LoggedInUserDetails::isAParticipantWithoutConfirmation()) {
          $renderArray[] = array(
            '#markup' => '<div class="eca_warning">' .
              iish_t('You have not finished the pre-registration for the @conference. Please go to the @link.',
                array(
                  '@conference' => CachedConferenceApi::getEventDate()->getLongNameAndYear(),
                  '@link' => $preRegistrationLink->toString()
                )) . '</div>'
          );

          // TODO Should we only allow payments of finished pre-registrations? if so remove next line
          $this->setPaymentStatus($renderArray, $participantDateDetails);
        }
        else {
          $renderArray[] = array(
            '#markup' => '<div class="eca_warning">' .
              iish_t('You are not registered for the @conference. Please go to the @link.',
                array(
                  '@conference' => CachedConferenceApi::getEventDate()->getLongNameAndYear(),
                  '@link' => $preRegistrationLink->toString()
                )) . '</div>'
          );
        }
      }
    }
  }

  /**
   * Creates the payment status field by calling the PayWay API
   *
   * @param array $renderArray The render array
   * @param ParticipantDateApi $participantDateDetails The participant of whom to check the payment status
   */
  private function setPaymentStatus(array &$renderArray, $participantDateDetails) {
    $paymentMethod = iish_t('Payment: none');
    $paymentStatus = iish_t('(Final registration and payment has not started yet)');
    $extraMessage = '';

    if ($this->moduleHandler()->moduleExists('iish_conference_finalregistration')) {
      $finalRegistrationLink = Link::fromTextAndUrl(iish_t('Final registration and payment'),
        Url::fromRoute('iish_conference_finalregistration.form'));

      $paymentStatus = iish_t('(Please go to @link)', array('@link' => $finalRegistrationLink->toString()));

      if (!is_null($participantDateDetails->getPaymentId()) && ($participantDateDetails->getPaymentId() !== 0)) {
        $orderDetails = new PayWayMessage(array('orderid' => $participantDateDetails->getPaymentId()));
        $order = $orderDetails->send('orderDetails');

        if (!empty($order)) {
          switch ($order->get('paymentmethod')) {
            case PayWayMessage::ORDER_OGONE_PAYMENT:
              $paymentMethod = iish_t('Payment: online payment');
              break;
            case PayWayMessage::ORDER_BANK_PAYMENT:
              $paymentMethod = iish_t('Payment: bank transfer');
              break;
            case PayWayMessage::ORDER_CASH_PAYMENT:
              $paymentMethod = iish_t('Payment: on site');
              break;
            default:
              $paymentMethod = iish_t('Payment unknown');
          }

          switch ($order->get('payed')) {
            case PayWayMessage::ORDER_NOT_PAYED:
              $paymentStatus = iish_t('(your payment has not yet been confirmed)');

              switch ($order->get('paymentmethod')) {
                case PayWayMessage::ORDER_BANK_PAYMENT:
                  $extraMessage = '<br/>' . iish_t('When we receive your bank payment we will confirm your payment.')
                    . '<br/>' . iish_t('If you have completed your bank payment and it is still not visible, please contact the conference secretariat.')
                    . '<br/>' . iish_t('You can also still pay online @link', array('@link' => $finalRegistrationLink->toString()));
                  break;
                case PayWayMessage::ORDER_CASH_PAYMENT:
                  $extraMessage = '<br/>' . iish_t('Your payment will be confirmed when you pay the fee at the conference.')
                    . '<br/>' . iish_t('You can still decide to pay online @link', array('@link' => $finalRegistrationLink->toString()));
                  break;
              }

              break;
            case PayWayMessage::ORDER_PAYED:
              $paymentStatus = iish_t('(your payment has been confirmed)');
              break;
            case PayWayMessage::ORDER_REFUND_OGONE:
            case PayWayMessage::ORDER_REFUND_BANK:
              $paymentStatus = iish_t('(your payment has been refunded)');
              break;
            default:
              $paymentStatus = iish_t('(status of your payment is unknown)');
              $extraMessage = '<br/>' . iish_t('If you have completed your payment please contact the conference secretariat.') .
                '<br/>'. iish_t('Otherwise please try again @link', array('@link' => $finalRegistrationLink->toString()));
          }
        }
        else {
          $paymentMethod = iish_t('Payment information is currently unavailable');
          $paymentStatus = '';
        }
      }
    }

    $renderArray[] = array(
      '#markup' => '<div class="bottommargin">' .
        trim($paymentMethod . ' ' . $paymentStatus) . $extraMessage .
        '</div>'
    );
  }

  /**
   * Creates the sessions containers for the personal page
   *
   * @param array $renderArray The render array
   * @param UserApi $userDetails The user in question
   * @param ParticipantDateApi|null $participantDateDetails The user in question participant details, if registered
   */
  private function setSessionsInfo(array &$renderArray, $userDetails, $participantDateDetails) {
    if (LoggedInUserDetails::isAParticipant()) {
      $papers = $userDetails->getPapers();
      $sessions = SessionParticipantApi::getAllSessions($userDetails->getSessionParticipantInfo());

      foreach ($sessions as $i => $session) {
        $sessionPapers = PaperApi::getPapersWithSession($papers, $session->getId());

        $header = iish_t('Session @count of @total', array(
          '@count' => $i + 1,
          '@total' => count($sessions)
        ));

        $fields = array(
          array('header' => $header)
        );

        $this->setSessionInfo($fields, $userDetails, $participantDateDetails, $sessionPapers, $session);

        $renderArray[] = array(
          '#theme' => 'iish_conference_container',
          '#fields' => $fields
        );
      }
    }
  }

  /**
   * Adds session info to a session content holder
   *
   * @param array $renderArray The render array
   * @param UserApi $userDetails The user in this session
   * @param ParticipantDateApi $participantDateDetails The participant in this session
   * @param PaperApi[] $sessionPapers The papers in this session
   * @param SessionApi $session The session in question
   */
  private function setSessionInfo(array &$renderArray, $userDetails, $participantDateDetails, $sessionPapers, $session) {
    if (SettingsApi::getSetting(SettingsApi::SHOW_NETWORK) == 1) {
      $networks = $session->getNetworks();
      foreach ($networks as $network) {
        $renderArray[] = array(
          'label' => 'Network name',
          'value' => $network->getName()
        );

        $renderArray[] = array(
          'label' => 'Chairs of this network',
          'value' => implode(', ', $network->getChairs())
        );

        $renderArray[] = new ConferenceHTML('<br />', TRUE);
      }
    }

    $sessionName = $session->getName() . ' <em>(' . iish_t($session->getState()->getDescription()) . ')</em>';
    $renderArray[] = array(
      'label' => 'Session name',
      'value' => $sessionName,
      'html' => TRUE
    );

    if (SettingsApi::getSetting(SettingsApi::SHOW_SESSION_TYPES) == 1) {
      $renderArray[] = array(
        'label' => 'Session type',
        'value' => $session->getType()
      );
    }

    $planned = CRUDApiMisc::getFirstWherePropertyEquals(new SessionRoomDateTimeApi(), 'session_id', $session->getId());
    if ($planned !== NULL) {
      // show session end time or only start time?
      $sessionTime = $planned->getDateTimePeriod();
      if (SettingsApi::getSetting(SettingsApi::SHOW_SESSION_ENDTIME_IN_PP) == '0') {
        $sessionTime = explode('-', $sessionTime);
        $sessionTime = $sessionTime[0];
      }

      $plannedText = '<span class="eca_warning heavy">'
        . $planned->getDay()->getDayFormatted("l d F Y")
        . ' / ' . $sessionTime . ' / '
        . $planned->getRoomName() . '</span>';

      $renderArray[] = array(
        'label' => 'Session Date / Time / Room',
        'value' => $plannedText,
        'html' => TRUE
      );
    }

    $submittedBy = (is_object($session->getAddedBy())) ? $session->getAddedBy()->getFullName() : NULL;
    $renderArray[] = array(
      'label' => 'Session submitted by',
      'value' => $submittedBy
    );

    $functionsInSession = SessionParticipantApi::getAllTypesOfUserForSession(
      $userDetails->getSessionParticipantInfo(),
      $userDetails->getId(),
      $session->getId()
    );

    $renderArray[] = array(
      'label' => 'Your function in session',
      'value' => implode(', ', $functionsInSession)
    );

    $renderArray[] = array(
      'label' => 'Session abstract',
      'value' => ConferenceMisc::getHTMLForLongText($session->getAbstr()),
      'html' => TRUE,
      'newLine' => TRUE
    );

    $renderArray[] = new ConferenceHTML('<br />', TRUE);
    $renderArray[] = array('header' => iish_t('Paper'));

    if (count($sessionPapers) > 0) {
      foreach ($sessionPapers as $paper) {
        $this->setPaperInfo($renderArray, $paper, $participantDateDetails);
      }
    }
    else {
      // TODO: Why no show?
      $renderArray[] = array('#markup' => iish_t('No paper.'));
    }
  }

  /**
   * Adds paper info to a paper content holder
   *
   * @param array $renderArray The render array
   * @param PaperApi $paper The paper in question
   * @param ParticipantDateApi $participant The participant of this paper
   */
  private function setPaperInfo(array &$renderArray, $paper, $participant) {
    $renderArray[] = array(
      'label' => 'Title',
      'value' => $paper->getTitle()
    );

    $renderArray[] = array(
      'label' => 'Paper state',
      'value' => $paper->getState()->getDescription()
    );

    $renderArray[] = array(
      'label' => 'Abstract',
      'value' => ConferenceMisc::getHTMLForLongText($paper->getAbstr()),
      'html' => TRUE,
      'newLine' => TRUE
    );

    if (SettingsApi::getSetting(SettingsApi::SHOW_PAPER_TYPE_OF_CONTRIBUTION) == 1) {
      $renderArray[] = array(
        'label' => 'Type of contribution',
        'value' => $paper->getTypeOfContribution()
      );
    }

    $renderArray[] = array(
      'label' => 'Co-author(s)',
      'value' => $paper->getCoAuthors()
    );

    if ((SettingsApi::getSetting(SettingsApi::SHOW_AWARD) == 1) && $participant->getStudent()) {
      try {
        $awardLink = Link::fromTextAndUrl(iish_t('more about the award'),
          Url::fromUri(SettingsApi::getSetting(SettingsApi::AWARD_URI)));
      }
      catch (\InvalidArgumentException $exception) {
        $awardLink = NULL;
      }

      $awardText = ConferenceMisc::getYesOrNo($participant->getAward());
      if ($awardLink !== NULL) {
        $awardText .= '&nbsp; <em>(' . $awardLink->toString() . ')</em>';
      }

      $renderArray[] = array(
        'label' => SettingsApi::getSetting(SettingsApi::AWARD_NAME) . '?',
        'value' => $awardText,
        'html' => TRUE
      );
    }

    $renderArray[] = array(
      'label' => 'Audio/visual equipment',
      'value' => implode(', ', $paper->getEquipment())
    );

    $renderArray[] = array(
      'label' => 'Extra audio/visual request',
      'value' => $paper->getEquipmentComment()
    );

    $renderArray[] = new ConferenceHTML('<br/>', TRUE);

    if ($paper->getFileName() == NULL) {
      $uploadPaperLink = Link::fromTextAndUrl(iish_t('Upload paper'),
        Url::fromRoute('iish_conference_personalpage.upload_paper', array(
          'paper' => $paper->getId()
        )));

      $renderArray[] = array(
        '#markup' => '<span class="heavy"> '
          . $uploadPaperLink->toString() . '</span>'
      );
    }
    else {
      $downloadPaperLink = Link::fromTextAndUrl($paper->getFileName(),
        Url::fromUri($paper->getDownloadURL()));

      $uploadPaperLink = Link::fromTextAndUrl($paper->getFileName(),
        Url::fromRoute('iish_conference_personalpage.upload_paper', array(
          'paper' => $paper->getId()
        )));

      $renderArray[] = array(
        'label' => 'Uploaded paper',
        'value' => $downloadPaperLink->toString()
          . '&nbsp; <em>(' . $uploadPaperLink->toString() . ')</em>',
        'html' => TRUE
      );
    }
  }

  /**
   * Creates the papers containers for the personal page
   *
   * @param array $renderArray The render array
   * @param UserApi $userDetails The user in question
   * @param ParticipantDateApi|null $participantDateDetails The user in question participant details, if registered
   */
  private function setPapersInfo(array &$renderArray, $userDetails, $participantDateDetails) {
    if (LoggedInUserDetails::isAParticipant()) {
      $papers = $userDetails->getPapers();
      $noSessionPapers = PaperApi::getPapersWithoutSession($papers);

      foreach ($noSessionPapers as $i => $paper) {
        $header = iish_t('Paper @count of @total', array(
          '@count' => $i + 1,
          '@total' => count($noSessionPapers)
        ));

        $fields = array(
          array('header' => $header)
        );

        $this->setPaperInfo($fields, $paper, $participantDateDetails);

        $renderArray[] = array(
          '#theme' => 'iish_conference_container',
          '#fields' => $fields
        );
      }
    }
  }

  /**
   * Creates the chair/discussant volunteering content for the personal page
   *
   * @param array $renderArray The render array
   * @param ParticipantDateApi|null $participantDateDetails The user in question participant details, if registered
   */
  private function setChairDiscussantInfo(array &$renderArray, $participantDateDetails) {
    $showChairDiscussant = (SettingsApi::getSetting(SettingsApi::SHOW_CHAIR_DISCUSSANT_POOL) == 1);

    if (LoggedInUserDetails::isAParticipant() && $showChairDiscussant) {
      $fields = array();
      $allVolunteering = $participantDateDetails->getParticipantVolunteering();

      $networksAsChair = ParticipantVolunteeringApi::getAllNetworksForVolunteering($allVolunteering, VolunteeringApi::CHAIR);
      $networksAsDiscussant = ParticipantVolunteeringApi::getAllNetworksForVolunteering($allVolunteering, VolunteeringApi::DISCUSSANT);

      CRUDApiClient::sort($networksAsChair);
      CRUDApiClient::sort($networksAsDiscussant);

      $fields[] = array('header' => iish_t('Chair / Discussant pool'));

      $fields[] = array(
        'label' => 'I would like to volunteer as Chair?',
        'value' => ConferenceMisc::getYesOrNo(count($networksAsChair) > 0)
      );

      if ((SettingsApi::getSetting(SettingsApi::SHOW_NETWORK) == 1) && (count($networksAsChair) > 0)) {
        $fields[] = array(
          'label' => 'Networks',
          'value' => implode(', ', $networksAsChair)
        );
      }

      $fields[] = array(
        'label' => 'I would like to volunteer as Discussant?',
        'value' => ConferenceMisc::getYesOrNo(count($networksAsDiscussant) > 0)
      );

      if ((SettingsApi::getSetting(SettingsApi::SHOW_NETWORK) == 1) && (count($networksAsDiscussant) > 0)) {
        $fields[] = array(
          'label' => 'Networks',
          'value' => implode(', ', $networksAsDiscussant)
        );
      }

      $renderArray[] = array(
        '#theme' => 'iish_conference_container',
        '#fields' => $fields
      );
    }
  }

  /**
   * Creates the language coach/pupil volunteering content for the personal page
   *
   * @param array $renderArray The render array
   * @param ParticipantDateApi|null $participantDateDetails The user in question participant details, if registered
   */
  private function setLanguageInfo(array &$renderArray, $participantDateDetails) {
    $showLanguage = (SettingsApi::getSetting(SettingsApi::SHOW_LANGUAGE_COACH_PUPIL) == 1);

    if (LoggedInUserDetails::isAParticipant() && $showLanguage) {
      $fields = array();
      $allVolunteering = $participantDateDetails->getParticipantVolunteering();

      $networksAsCoach = ParticipantVolunteeringApi::getAllNetworksForVolunteering($allVolunteering, VolunteeringApi::COACH);
      $networksAsPupil = ParticipantVolunteeringApi::getAllNetworksForVolunteering($allVolunteering, VolunteeringApi::PUPIL);

      CRUDApiClient::sort($networksAsCoach);
      CRUDApiClient::sort($networksAsPupil);

      $languageFound = FALSE;
      $fields[] = array('header' => iish_t('English Language Coach'));

      if ((SettingsApi::getSetting(SettingsApi::SHOW_NETWORK) == 1) && (count($networksAsCoach) > 0)) {
        $languageFound = TRUE;
        $fields[] = array(
          'label' => iish_t('I would like to be an English Language Coach in the following networks'),
          'value' => implode(', ', $networksAsCoach),
        );
      }
      else {
        if (count($networksAsCoach) > 0) {
          $languageFound = TRUE;
          $fields[] = array(
            'label' => iish_t('I would like to be an English Language Coach'),
            'value' => ConferenceMisc::getYesOrNo(TRUE),
          );
        }
      }

      if (count($networksAsPupil) > 0) {
        $languageFound = TRUE;
        $networksAndUsers = ParticipantVolunteeringApi::getAllUsersWithTypeForNetworks(VolunteeringApi::COACH, $networksAsPupil);

        $list = array();
        foreach ($networksAsPupil as $network) {
          CRUDApiClient::sort($networksAndUsers[$network->getId()]);

          $emailList = array();
          if (is_array($networksAndUsers[$network->getId()])) {
            foreach ($networksAndUsers[$network->getId()] as $user) {
              $emailList[] = Link::fromTextAndUrl($user->getFullName(),
                Url::fromUri('mailto:' . $user->getEmail()));
            }
          }

          if (count($emailList) > 0) {
            if (SettingsApi::getSetting(SettingsApi::SHOW_NETWORK) == 1) {
              $list[] = '<strong>' . $network->getName() . '</strong>: '
                . ConferenceMisc::getEnumSingleLine($emailList);
            }
            else {
              $list[] = ConferenceMisc::getEnumSingleLine($emailList);
            }
          }
          else {
            if (SettingsApi::getSetting(SettingsApi::SHOW_NETWORK) == 1) {
              $list[] = '<strong>' . $network->getName() . '</strong>: <em>'
                . iish_t('No language coaches found in this network!') . '</em>';
            }
            else {
              $list[] = '<em>' . iish_t('No language coaches found!') . '</em>';
            }
          }
        }

        if (SettingsApi::getSetting(SettingsApi::SHOW_NETWORK) == 1) {
          $languageCoachLabel = iish_t('I need some help from one of the following English Language Coaches '
            . 'in each chosen network');
        }
        else {
          $languageCoachLabel = iish_t('I need some help from one of the following English Language Coaches');
        }

        $fields[] = array(
          'label' => $languageCoachLabel,
          'value' => array('#theme' => 'item_list', '#items' => $list),
          'html' => TRUE,
          'newLine' => TRUE
        );
      }

      if (!$languageFound) {
        $fields[] = array('#markup' => '<em>' . ConferenceMisc::getLanguageCoachPupil('') . '</em>');
      }

      $renderArray[] = array(
        '#theme' => 'iish_conference_container',
        '#fields' => $fields
      );
    }
  }

  /**
   * Creates the links for the personal page
   *
   * @param array $renderArray The render array
   * @param ParticipantDateApi|null $participantDateDetails The user in question participant details, if registered
   */
  private function setLinks(array &$renderArray, $participantDateDetails) {
    $fields = array(
      array('#markup' => '<a name="links"></a>'),
      array('header' => iish_t('Links')),
    );

    // Show pre registration link if not registered or participant state is 'not finished' or 'new participant'
    if ($this->moduleHandler()->moduleExists('iish_conference_preregistration') && (is_null($participantDateDetails) ||
        $participantDateDetails->getStateId() === ParticipantStateApi::DID_NOT_FINISH_REGISTRATION ||
        $participantDateDetails->getStateId() === ParticipantStateApi::NEW_PARTICIPANT)
    ) {
      $fields[] = array(
        '#markup' => '&bull; ' . Link::fromTextAndUrl(iish_t('Pre-registration form'),
            Url::fromRoute('iish_conference_preregistration.form'))->toString()
          . '<br />'
      );
    }

    if ($this->moduleHandler()->moduleExists('iish_conference_changepassword')) {
      $fields[] = array(
        '#markup' => '&bull; ' . Link::fromTextAndUrl(iish_t('Change password'),
            Url::fromRoute('iish_conference_changepassword.form'))->toString()
          . '<br />'
      );
    }

    if ($this->moduleHandler()->moduleExists('iish_conference_finalregistration')) {
      $fields[] = array(
        '#markup' => '&bull; ' . Link::fromTextAndUrl(iish_t('Final registration and payment'),
            Url::fromRoute('iish_conference_finalregistration.form'))->toString()
          . '<br />'
      );
    }

    if ($this->moduleHandler()->moduleExists('iish_conference_emails')) {
      $fields[] = array(
        '#markup' => '&bull; ' . Link::fromTextAndUrl(iish_t('List of e-mails sent to you'),
            Url::fromRoute('iish_conference_emails.index'))->toString()
          . '<br />'
      );
    }

    if ($this->moduleHandler()->moduleExists('iish_conference_login_logout')) {
      $fields[] = array(
        '#markup' => '&bull; ' . Link::fromTextAndUrl(iish_t('Logout'),
            Url::fromRoute('iish_conference_login_logout.logout_form'))->toString()
          . '<br />'
      );
    }

    // Check if live or crew or network chair or chair or organizer
    if ($this->moduleHandler()->moduleExists('iish_conference_programme') && ConferenceMisc::mayLoggedInUserSeeProgramme()) {
      $fields[] = array(
        '#markup' => '&bull; ' . Link::fromTextAndUrl(iish_t('Preliminary Programme'),
            Url::fromRoute('iish_conference_programme.index'))->toString()
          . '<br />'
      );
    }

    $renderArray[] = array(
      '#theme' => 'iish_conference_container',
      '#fields' => $fields
    );
  }

  /**
   * Creates the network links for the personal page
   *
   * @param array $renderArray The render array
   * @param ParticipantDateApi|null $participantDateDetails The user in question participant details, if registered
   */
  private function setLinksNetwork(array &$renderArray, $participantDateDetails) {
    if (LoggedInUserDetails::hasFullRights() || LoggedInUserDetails::isNetworkChair()) {
      $fields = array(
        array('#markup' => '<a name="nclinks"></a>'),
        array('header' => iish_t('Links for network chairs')),
      );

      // Names and email addresses
      if ($this->moduleHandler()->moduleExists('iish_conference_network_participants_xls')) {
        $fields[] = array(
          '#markup' => '&bull; ' . Link::fromTextAndUrl(iish_t('Participant names and e-mail addresses'),
              Url::fromRoute('iish_conference_network_participants_xls.index'))->toString()
            . ' (xls) <br />'
        );
      }

      // Session paper proposals
      if ($this->moduleHandler()->moduleExists('iish_conference_network_forchairs')) {
        $fields[] = array(
          '#markup' => '&bull; ' . Link::fromTextAndUrl(iish_t('Participant and their papers'),
              Url::fromRoute('iish_conference_network_forchairs.index'))->toString()
            . '<br />'
        );
      }

      // Session paper proposals xls (new and accepted participants)
      if ($this->moduleHandler()->moduleExists('iish_conference_network_sessionpapers_xls')) {
        $fields[] = array(
          '#markup' => '&bull; ' . Link::fromTextAndUrl(iish_t('Participants and their session paper proposals (new and accepted participants)'),
              Url::fromRoute('iish_conference_network_sessionpapers_xls.index'))->toString()
            . ' (xls) <br />'
        );
      }

      // Session paper proposals xls (only accepted participants)
      if ($this->moduleHandler()->moduleExists('iish_conference_network_sessionpapersaccepted_xls')) {
        $fields[] = array(
          '#markup' => '&bull; ' . Link::fromTextAndUrl(iish_t('Participants and their session paper proposals (only accepted participants)'),
              Url::fromRoute('iish_conference_network_sessionpapersaccepted_xls.index'))->toString()
            . ' (xls) <br />'
        );
      }

      // TODO: Why commented out?
      // Individual paper proposals
      if ($this->moduleHandler()->moduleExists('iish_conference_network_proposedparticipants')) {
        $fields[] = array(
          '#markup' => '&bull; ' . Link::fromTextAndUrl(iish_t('Participants and their individual paper proposals)'),
              Url::fromRoute('iish_conference_network_proposedparticipants.index'))->toString()
            . '<br />'
        );
      }

      // Individual paper proposals xls
      if ($this->moduleHandler()->moduleExists('iish_conference_network_individualpapers_xls')) {
        $fields[] = array(
          '#markup' => '&bull; ' . Link::fromTextAndUrl(iish_t('Participants and their individual paper proposals)'),
              Url::fromRoute('iish_conference_network_individualpapers_xls.index'))->toString()
            . ' (xls) <br />'
        );
      }

      // Volunteers
      if ($this->moduleHandler()->moduleExists('iish_conference_network_volunteers')) {
        $fields[] = array(
          '#markup' => '&bull; ' . Link::fromTextAndUrl(iish_t('Volunteers (Chair/Discussant)'),
              Url::fromRoute('iish_conference_network_volunteers.index'))->toString()
            . '<br />'
        );
      }

      // Election advisory
      if ($this->moduleHandler()->moduleExists('iish_conference_electionadvisory')) {
        $fields[] = array(
          '#markup' => '&bull; ' . Link::fromTextAndUrl(iish_t('Election \'Advisory board\''),
              Url::fromRoute('iish_conference_electionadvisory.form'))->toString()
            . '<br />'
        );
      }

      $renderArray[] = array(
        '#theme' => 'iish_conference_container',
        '#fields' => $fields
      );
    }
  }
}
