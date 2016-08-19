<?php
namespace Drupal\iish_conference_programme\Controller;

use Drupal\Core\Controller\ControllerBase;

use Drupal\iish_conference\API\SettingsApi;
use Drupal\iish_conference\API\ApiCriteriaBuilder;
use Drupal\iish_conference\API\CachedConferenceApi;
use Drupal\iish_conference\API\ConferenceApiClient;
use Drupal\iish_conference\API\LoggedInUserDetails;

use Drupal\iish_conference\API\Domain\DayApi;
use Drupal\iish_conference\API\Domain\RoomApi;
use Drupal\iish_conference\API\Domain\PaperApi;
use Drupal\iish_conference\API\Domain\SessionApi;
use Drupal\iish_conference\API\Domain\NetworkApi;
use Drupal\iish_conference\API\Domain\EventDateApi;
use Drupal\iish_conference\API\Domain\SessionDateTimeApi;
use Drupal\iish_conference\API\Domain\ParticipantTypeApi;

use Drupal\iish_conference\Highlighter;
use Drupal\iish_conference\EasyProtection;
use Drupal\iish_conference\ConferenceMisc;

use Drupal\iish_conference_programme\API\ProgrammeApi;

use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * The controller for the programme.
 */
class ProgrammeController extends ControllerBase {

  /**
   * Returns the programme.
   */
  public function programme() {
    $yearCode = \Drupal::request()->query->get('yearCode');
    $eventDate = $this->getEventDate($yearCode);
    if ($eventDate === NULL) {
      drupal_set_message(iish_t('No programme available for the given year!'), 'error');
      return $this->redirect('iish_conference_programme.index');
    }

    // If the programme of the last event date is still closed, show message
    if ($eventDate->isLastDate() && !ConferenceMisc::mayLoggedInUserSeeProgramme()) {
      drupal_set_message(iish_t('The programme is not yet available.'), 'warning');
      return array();
    }

    // If the programme of the last event date is still under construction AND there is an under construction message, show message
    $underConstruction = SettingsApi::getSetting(SettingsApi::ONLINE_PROGRAM_UNDER_CONSTRUCTION);
    if ($eventDate->isLastDate() && ($underConstruction != '')) {
      drupal_set_message($underConstruction, 'warning');
    }

    // Make sure the settings are already cached, before changing the year code
    CachedConferenceApi::getSettings();
    ConferenceApiClient::setYearCode($eventDate->getYearCodeURL());
    $params = \Drupal::request()->query;

    // Obtain all necessary query parameters
    $dayId = $params->get('day') ? EasyProtection::easyIntegerProtection($params->get('day')) : NULL;
    $timeId = $params->get('time') ? EasyProtection::easyIntegerProtection($params->get('time')) : NULL;
    $roomId = $params->get('room') ? EasyProtection::easyIntegerProtection($params->get('room')) : NULL;
    $networkId = $params->get('network') ? EasyProtection::easyIntegerProtection($params->get('network')) : NULL;
    $sessionId = $params->get('session') ? EasyProtection::easyIntegerProtection($params->get('session')) : NULL;
    $participantId = ($params->get('favorites') && LoggedInUserDetails::isLoggedIn()) ? LoggedInUserDetails::getParticipant()->getId() : NULL;
    $textsearch = $params->get('textsearch') ? EasyProtection::easyStringProtection($params->get('textsearch')) : NULL;

    // Make sure the query parameters representing ids are integers and empty strings are null
    $dayId = (is_int($dayId)) ? $dayId : NULL; // An id of 0 is allowed, means all days
    $timeId = (is_int($timeId) && ($timeId !== 0)) ? $timeId : NULL;
    $roomId = (is_int($roomId) && ($roomId !== 0)) ? $roomId : NULL;
    $networkId = (is_int($networkId) && ($networkId !== 0)) ? $networkId : NULL;
    $sessionId = (is_int($sessionId) && ($sessionId !== 0)) ? $sessionId : NULL;
    $textsearch = (!is_null($textsearch) && (strlen($textsearch) > 0)) ? urldecode($textsearch) : NULL;

    $props = new ApiCriteriaBuilder();
    $days = DayApi::getListWithCriteria($props->get())->getResults();
    $networks = NetworkApi::getListWithCriteria($props->get())->getResults();
    $rooms = RoomApi::getListWithCriteria($props->get())->getResults();
    $dateTimes = SessionDateTimeApi::getListWithCriteria($props->get())->getResults();
    $types = ParticipantTypeApi::getListWithCriteria($props->get())->getResults();

    // Make sure we filter out co-authors and types with papers and types configured to be hidden
    $alwaysHide = SettingsApi::getSetting(SettingsApi::HIDE_ALWAYS_IN_ONLINE_PROGRAMME);
    $typesToHide = SettingsApi::getArrayOfValues($alwaysHide);
    foreach ($types as $i => $type) {
      if (($type->getId() == ParticipantTypeApi::CO_AUTHOR_ID) ||
        $type->getWithPaper() ||
        (array_search($type->getId(), $typesToHide) !== FALSE)
      ) {
        unset($types[$i]);
      }
    }
    $types = array_values($types);

    // What time slot is showing?
    $showing = '';
    $showingTimeSlot = iish_t('all days');

    // if network id, room id or text search is not empty, then show all days
    if (!is_null($networkId) || !is_null($roomId) || !is_null($participantId) || !is_null($textsearch)) {
      $dayId = 0; // all days
      $timeId = NULL;

      if (!is_null($networkId)) {
        foreach ($networks as $network) {
          if ($network->getId() === $networkId) {
            $showing = $network->getName();
          }
        }

        $roomId = NULL;
        $participantId = NULL;
        $textsearch = NULL;
      }
      else {
        if (!is_null($roomId)) {
          foreach ($rooms as $room) {
            if ($room->getId() === $roomId) {
              $showing = iish_t('room') . ' ' . $room->getRoomNumber();
            }
          }

          $networkId = NULL;
          $participantId = NULL;
          $textsearch = NULL;
        }
        else {
          if (!is_null($participantId)) {
            $showing = iish_t('Favorite sessions');
            $showingTimeSlot = '';

            $networkId = NULL;
            $roomId = NULL;
            $textsearch = NULL;
          }
          else {
            if (!is_null($textsearch)) {
              $showing = iish_t('text search') . ': ' . $textsearch;

              $networkId = NULL;
              $roomId = NULL;
              $participantId = NULL;
            }
          }
        }
      }
    }
    else {
      $showing = iish_t('all days');
      $showingTimeSlot = '';
    }

    // if dayId is empty, only first date, else all dates
    if (is_null($dayId)) {
      $dayId = $days[0]->getId(); // find first date
      $showing = $days[0]->getDayFormatted("l j F Y");
      $showingTimeSlot = iish_t('entire day');
    }
    else {
      if ($dayId === 0) {
        $dayId = NULL;
      }
      else {
        foreach ($days as $day) {
          if ($day->getId() === $dayId) {
            $showing = $day->getDayFormatted("l j F Y");
            $showingTimeSlot = iish_t('entire day');
          }
        }
      }
    }

    if (!is_null($timeId)) {
      foreach ($dateTimes as $dateTime) {
        if ($dateTime->getId() === $timeId) {
          $showing .= ' ' . $dateTime->getPeriod(TRUE);
          $showingTimeSlot = iish_t('single time slot');
        }
      }
    }

    $curShowing = iish_t('Showing') . ': ' . $showing;
    $curShowing .= (strlen($showingTimeSlot) > 0) ? ' (' . $showingTimeSlot . ')' : '';

    // Create the query part for the back URL
    if (!is_null($textsearch)) {
      $backUrl = '?textsearch=' . urlencode($textsearch);
    }
    else {
      if (!is_null($participantId)) {
        $backUrl = '?favorites=yes';
      }
      else {
        if (!is_null($roomId)) {
          $backUrl = '?room=' . $roomId;
        }
        else {
          if (!is_null($networkId)) {
            $backUrl = '?network=' . $networkId;
          }
          else {
            $backUrl = "?day=" . $dayId . "&time=" . $timeId;
          }
        }
      }
    }

    $programmeApi = new ProgrammeApi();
    $programme = NULL;
    if (is_null($sessionId)) {
      $programme = $programmeApi->getProgramme($dayId, $timeId, $networkId, $roomId, $participantId, $textsearch);
    }
    else {
      $programme = $programmeApi->getProgrammeForSession($sessionId);
    }
    $this->prepareProgramme($programme, $textsearch);

    $downloadPaperLastDate = strtotime(SettingsApi::getSetting(SettingsApi::DOWNLOAD_PAPER_LASTDATE));
    $downloadPaperIsOpen = ConferenceMisc::isOpenForLastDate($downloadPaperLastDate);

    $participant = LoggedInUserDetails::getParticipant();
    $favoriteSessions = ($participant !== NULL) ? $participant->getFavoriteSessionsId() : array();

    return array(
      '#theme' => 'iish_conference_programme',
      '#networks' => $networks,
      '#eventDate' => $eventDate,
      '#days' => $days,
      '#dateTimes' => $dateTimes,
      '#types' => $types,
      '#programme' => $programme,
      '#backUrlQuery' => $backUrl,
      '#networkId' => $networkId,
      '#roomId' => $roomId,
      '#sessionId' => $sessionId,
      '#textsearch' => $textsearch,
      '#curShowing' => $curShowing,
      '#downloadPaperIsOpen' => $downloadPaperIsOpen,
      '#favoriteSessions' => $favoriteSessions,
      '#isParticipant' => LoggedInUserDetails::isAParticipant(),
    );
  }

  /**
   * Adds a session to the users favorite sessions.
   * @param SessionApi $session The session.
   * @return JsonResponse The JSON response.
   */
  public function addSession($session) {
    $output = array('success' => FALSE);

    if ($session !== NULL) {
      $output['session'] = $session->getId();

      if (LoggedInUserDetails::isLoggedIn()) {
        $participant = LoggedInUserDetails::getParticipant();

        $sessionIds = $participant->getFavoriteSessionsId();
        $sessionIds[] = $session->getId();
        $participant->setFavoriteSessionsId($sessionIds);

        $success = $participant->save(FALSE);
        $output['success'] = $success;
      }
    }

    return new JsonResponse($output);
  }

  /**
   * Removes a session to the users favorite sessions.
   * @param SessionApi $session The session.
   * @return JsonResponse The JSON response.
   */
  public function removeSession($session) {
    $output = array('success' => FALSE);

    if ($session !== NULL) {
      $output['session'] = $session->getId();

      if (LoggedInUserDetails::isLoggedIn()) {
        $participant = LoggedInUserDetails::getParticipant();

        $sessionIds = $participant->getFavoriteSessionsId();
        $sessionIds = array_diff($sessionIds, array($session->getId()));
        $participant->setFavoriteSessionsId($sessionIds);

        $success = $participant->save(FALSE);
        $output['success'] = $success;
      }
    }

    return new JsonResponse($output);
  }

  /**
   * Returns the title of the programme.
   * @return string The title.
   */
  public function getProgrammeTitle() {
    return SettingsApi::getSetting(SettingsApi::ONLINE_PROGRAM_HEADER);
  }

  /**
   * Returns the event date that belongs to the year code, if given.
   *
   * @param string|null $yearCode The year code.
   *
   * @return EventDateApi The event date.
   */
  private static function getEventDate($yearCode) {
    $eventDate = NULL;

    if (!empty($yearCode)) {
      foreach (CachedConferenceApi::getEventDates() as $date) {
        if (strtolower($date->getYearCodeURL()) == strtolower(trim($yearCode))) {
          $eventDate = $date;
        }
      }
    }
    else {
      $eventDate = CachedConferenceApi::getEventDate();
    }

    return $eventDate;
  }

  /**
   * Prepare the programme obtained from the API before templating.
   * @param array $programme The programme.
   * @param string $textsearch The text search.
   */
  private static function prepareProgramme(&$programme, $textsearch) {
    $highlight = new Highlighter(explode(' ', $textsearch));
    $highlight->setOpeningTag('<span class="highlight">');
    $highlight->setClosingTag('</span>');

    $alwaysHide = SettingsApi::getSetting(SettingsApi::HIDE_ALWAYS_IN_ONLINE_PROGRAMME);
    $typesToHide = SettingsApi::getArrayOfValues($alwaysHide);

    foreach ($programme as &$session) {
      $sessionName = $session['sessionName'];
      $session['sessionNameHl'] = $highlight->highlight($sessionName);

      $participantsWithPaper = array();
      $participantsPerType = array();
      foreach ($session['participants'] as &$participant) {
        $participantName = $participant['participantName'];
        $participant['participantNameHl'] = $highlight->highlight($participantName);

        if (array_key_exists('coAuthors', $participant)) {
          $coAuthors = $participant['coAuthors'];
          $participant['coAuthorsHl'] = $highlight->highlight($coAuthors);
        }

        if (array_key_exists('paperName', $participant)) {
          $paperName = $participant['paperName'];
          $participant['paperNameHl'] = $highlight->highlight($paperName);

          $paperId = $participant['paperId'];
          $participant['paperDownloadLink'] = PaperApi::getDownloadURLFor($paperId);
        }

        $typeId = $participant['typeId'];
        $isCoAuthor = ($typeId == ParticipantTypeApi::CO_AUTHOR_ID);
        $isNotHidden = (array_search($typeId, $typesToHide) === FALSE);

        if (!$isCoAuthor && $isNotHidden) {
          if (array_key_exists('paperId', $participant)) {
            $participantsWithPaper[] = $participant;
          }
          else {
            $participantsPerType[$typeId][] = $participant;
          }
        }
      }

      $session['participantsWithPaper'] = $participantsWithPaper;
      $session['participantsPerType'] = $participantsPerType;
    }
  }
}
