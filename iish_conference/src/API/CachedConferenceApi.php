<?php
namespace Drupal\iish_conference\API;

use Drupal\iish_conference\API\Domain\AgeRangeApi;
use Drupal\iish_conference\API\Domain\CountryApi;
use Drupal\iish_conference\API\Domain\DayApi;
use Drupal\iish_conference\API\Domain\EquipmentApi;
use Drupal\iish_conference\API\Domain\ExtraApi;
use Drupal\iish_conference\API\Domain\KeywordApi;
use Drupal\iish_conference\API\Domain\NetworkApi;
use Drupal\iish_conference\API\Domain\PaperStateApi;
use Drupal\iish_conference\API\Domain\PaperTypeApi;
use Drupal\iish_conference\API\Domain\ParticipantStateApi;
use Drupal\iish_conference\API\Domain\ParticipantTypeApi;
use Drupal\iish_conference\API\Domain\ReviewCriteriaApi;
use Drupal\iish_conference\API\Domain\RoomApi;
use Drupal\iish_conference\API\Domain\SessionApi;
use Drupal\iish_conference\API\Domain\EventDateApi;
use Drupal\iish_conference\API\Domain\SessionDateTimeApi;
use Drupal\iish_conference\API\Domain\SessionStateApi;
use Drupal\iish_conference\API\Domain\SessionTypeApi;
use Drupal\iish_conference\API\Domain\VolunteeringApi;

/**
 * Holds all methods to cache data obtained via the API and obtain the data again from the cache
 */
class CachedConferenceApi {
  private static $nameEventDateCache = 'iishconference_eventdate';
  private static $nameEventDatesCache = 'iishconference_eventdates';
  private static $nameNetworksCache = 'iishconference_networks';
  private static $nameCountriesCache = 'iishconference_countries';
  private static $nameAgeRangesCache = 'iishconference_age_ranges';
  private static $nameDaysCache = 'iishconference_days';
  private static $nameKeywordsCache = 'iishconference_keywords';
  private static $nameSessionDateTimesCache = 'iishconference_session_date_times';
  private static $nameParticipantTypesCache = 'iishconference_participant_types';
  private static $nameParticipantStatesCache = 'iishconference_participant_states';
  private static $nameSessionStatesCache = 'iishconference_session_states';
  private static $nameSessionTypesCache = 'iishconference_session_types';
  private static $namePaperStatesCache = 'iishconference_paper_states';
  private static $namePaperTypesCache = 'iishconference_paper_types';
  private static $nameEquipmentCache = 'iishconference_equipment';
  private static $nameRoomsCache = 'iishconference_rooms';
  private static $nameExtrasCache = 'iishconference_extras';
  private static $nameVolunteeringCache = 'iishconference_volunteering';
  private static $nameReviewCriteriaCache = 'iishconference_review_criteria';
  private static $nameSettingsCache = 'iishconference_settings';
  private static $nameTranslationsCache = 'iishconference_translations';
  private static $nameSessionsKeyValueCache = 'iishconference_sessions_key_value';

  /**
   * Updates all caches
   */
  public static function updateAll() {
    try {
      self::setEventDate();
      self::setEventDates();
      self::setNetworks();
      self::setCountries();
      self::setAgeRanges();
      self::setDays();
      self::setKeywords();
      self::setSessionDateTimes();
      self::setParticipantTypes();
      self::setParticipantStates();
      self::setSessionStates();
      self::setSessionTypes();
      self::setPaperStates();
      self::setPaperTypes();
      self::setEquipment();
      self::setRooms();
      self::setExtras();
      self::setVolunteering();
      self::setReviewCriteria();
      self::setSettings();
      self::setTranslations();
      self::setSessionsKeyValue();
    } catch (\Exception $exception) {
      \Drupal::logger('iish_conference')
        ->error('Failure to update the conference cache: ' . $exception->getMessage());
    }
  }

  /**
   * Cache the event date.
   *
   * @return EventDateApi
   */
  public static function setEventDate() {
    ConferenceApiClient::setYearCode(NULL);

    $eventDate = EventDateApi::getCurrent();
    \Drupal::cache()->set(self::$nameEventDateCache, $eventDate);

    return $eventDate;
  }

  /**
   * Cache the event dates.
   *
   * @return EventDateApi[]
   */
  public static function setEventDates() {
    ConferenceApiClient::setYearCode(NULL);

    $eventDates = EventDateApi::getAllForEvent();
    \Drupal::cache()->set(self::$nameEventDatesCache, $eventDates);

    return $eventDates;
  }

  /**
   * Cache the networks.
   *
   * @return NetworkApi[]
   */
  public static function setNetworks() {
    ConferenceApiClient::setYearCode(NULL);

    $results = NetworkApi::getListWithCriteria(array());
    if ($networks = $results->getResults()) {
      foreach ($networks as $network) {
        $network->getChairs();
      }
      \Drupal::cache()->set(self::$nameNetworksCache, $networks);

      return $networks;
    }

    return NULL;
  }

  /**
   * Cache the countries.
   *
   * @return CountryApi[]
   */
  public static function setCountries() {
    return self::set(self::$nameCountriesCache, CountryApi::class);
  }

  /**
   * Cache the age ranges.
   *
   * @return AgeRangeApi[]
   */
  public static function setAgeRanges() {
    return self::set(self::$nameAgeRangesCache, AgeRangeApi::class);
  }

  /**
   * Cache the days.
   *
   * @return DayApi[]
   */
  public static function setDays() {
    return self::set(self::$nameDaysCache, DayApi::class);
  }

  /**
   * Cache the keywords.
   *
   * @return KeywordApi[]
   */
  public static function setKeywords() {
    return self::set(self::$nameKeywordsCache, KeywordApi::class);
  }

  /**
   * Cache the session date/times.
   *
   * @return SessionDateTimeApi[]
   */
  public static function setSessionDateTimes() {
    return self::set(self::$nameSessionDateTimesCache, SessionDateTimeApi::class);
  }

  /**
   * Cache the participant types.
   *
   * @return ParticipantTypeApi[]
   */
  public static function setParticipantTypes() {
    return self::set(self::$nameParticipantTypesCache, ParticipantTypeApi::class);
  }

  /**
   * Cache the participant states.
   *
   * @return ParticipantStateApi[]
   */
  public static function setParticipantStates() {
    return self::set(self::$nameParticipantStatesCache, ParticipantStateApi::class);
  }

  /**
   * Cache the session states.
   *
   * @return SessionStateApi[]
   */
  public static function setSessionStates() {
    return self::set(self::$nameSessionStatesCache, SessionStateApi::class);
  }

  /**
   * Cache the session types.
   *
   * @return SessionTypeApi[]
   */
  public static function setSessionTypes() {
    return self::set(self::$nameSessionTypesCache, SessionTypeApi::class);
  }

  /**
   * Cache the papers.
   *
   * @return PaperStateApi[]
   */
  public static function setPaperStates() {
    return self::set(self::$namePaperStatesCache, PaperStateApi::class);
  }

  /**
   * Cache the paper types.
   *
   * @return PaperTypeApi[]
   */
  public static function setPaperTypes() {
    return self::set(self::$namePaperTypesCache, PaperTypeApi::class);
  }

  /**
   * Cache the equipment.
   *
   * @return EquipmentApi[]
   */
  public static function setEquipment() {
    return self::set(self::$nameEquipmentCache, EquipmentApi::class);
  }

  /**
   * Cache the rooms.
   *
   * @return RoomApi[]
   */
  public static function setRooms() {
    return self::set(self::$nameRoomsCache, RoomApi::class);
  }

  /**
   * Cache the extras.
   *
   * @return ExtraApi[]
   */
  public static function setExtras() {
    return self::set(self::$nameExtrasCache, ExtraApi::class);
  }

  /**
   * Cache the volunteering options.
   *
   * @return VolunteeringApi[]
   */
  public static function setVolunteering() {
    return self::set(self::$nameVolunteeringCache, VolunteeringApi::class);
  }

  /**
   * Cache the review criteria.
   *
   * @return ReviewCriteriaApi[]
   */
  public static function setReviewCriteria() {
    return self::set(self::$nameReviewCriteriaCache, ReviewCriteriaApi::class);
  }

  /**
   * Cache the settings.
   *
   * @return array
   */
  public static function setSettings() {
    ConferenceApiClient::setYearCode(NULL);

    $settingsApi = new SettingsApi();
    $settings = $settingsApi->settings();
    \Drupal::cache()->set(self::$nameSettingsCache, $settings);

    return $settings;
  }

  /**
   * Cache the translations.
   *
   * @return array
   */
  public static function setTranslations() {
    ConferenceApiClient::setYearCode(NULL);

    $translationsApi = new TranslationsApi();
    $translations = $translationsApi->translations();
    \Drupal::cache()->set(self::$nameTranslationsCache, $translations);

    return $translations;
  }

  /**
   * Cache the session keys and values.
   *
   * @return array
   */
  public static function setSessionsKeyValue() {
    ConferenceApiClient::setYearCode(NULL);

    $props = new ApiCriteriaBuilder();
    $sessions = SessionApi::getListWithCriteria(
      $props
        ->sort('name', 'asc')
        ->get()
    )->getResults();
    $sessionsKeyValue = SessionApi::getAsKeyValueArray($sessions);

    \Drupal::cache()
      ->set(self::$nameSessionsKeyValueCache, $sessionsKeyValue);

    return $sessionsKeyValue;
  }

  /**
   * Get the cached event date.
   *
   * @return EventDateApi
   */
  public static function getEventDate() {
    if ($result = \Drupal::cache()->get(self::$nameEventDateCache)) {
      return $result->data;
    }
    else {
      return self::setEventDate();
    }
  }

  /**
   * Get the cached event dates.
   *
   * @return EventDateApi[]
   */
  public static function getEventDates() {
    if ($result = \Drupal::cache()->get(self::$nameEventDatesCache)) {
      return $result->data;
    }
    else {
      return self::setEventDates();
    }
  }

  /**
   * Get the cached networks.
   *
   * @return NetworkApi[]
   */
  public static function getNetworks() {
    if ($result = \Drupal::cache()->get(self::$nameNetworksCache)) {
      return $result->data;
    }
    else {
      return self::setNetworks();
    }
  }

  /**
   * Get the cached rooms.
   *
   * @return RoomApi[]
   */
  public static function getRooms() {
    return self::get(self::$nameRoomsCache, RoomApi::class);
  }

  /**
   * Get the cached countries.
   *
   * @return CountryApi[]
   */
  public static function getCountries() {
    return self::get(self::$nameCountriesCache, CountryApi::class);
  }

  /**
   * Get the cached age ranges.
   *
   * @return AgeRangeApi[]
   */
  public static function getAgeRanges() {
    return self::get(self::$nameAgeRangesCache, AgeRangeApi::class);
  }

  /**
   * Get the cached days.
   *
   * @return DayApi[]
   */
  public static function getDays() {
    return self::get(self::$nameDaysCache, DayApi::class);
  }

  /**
   * Get the cached keywords.
   *
   * @return KeywordApi[]
   */
  public static function getKeywords() {
    return self::get(self::$nameKeywordsCache, KeywordApi::class);
  }

  /**
   * Get the cached extras.
   *
   * @return ExtraApi[]
   */
  public static function getExtras() {
    return self::get(self::$nameExtrasCache, ExtraApi::class);
  }

  /**
   * Get the cached session date/times.
   *
   * @return SessionDateTimeApi[]
   */
  public static function getSessionDateTimes() {
    return self::get(self::$nameSessionDateTimesCache, SessionDateTimeApi::class);
  }

  /**
   * Get the cached participant types.
   *
   * @return ParticipantTypeApi[]
   */
  public static function getParticipantTypes() {
    return self::get(self::$nameParticipantTypesCache, ParticipantTypeApi::class);
  }

  /**
   * Get the cached participant states.
   *
   * @return ParticipantStateApi[]
   */
  public static function getParticipantStates() {
    return self::get(self::$nameParticipantStatesCache, ParticipantStateApi::class);
  }

  /**
   * Get the cached session states.
   *
   * @return SessionStateApi[]
   */
  public static function getSessionStates() {
    return self::get(self::$nameSessionStatesCache, SessionStateApi::class);
  }

  /**
   * Get the cached session types.
   *
   * @return SessionTypeApi[]
   */
  public static function getSessionTypes() {
    return self::get(self::$nameSessionTypesCache, SessionTypeApi::class);
  }

  /**
   * Get the cached paper states.
   *
   * @return PaperStateApi[]
   */
  public static function getPaperStates() {
    return self::get(self::$namePaperStatesCache, PaperStateApi::class);
  }

  /**
   * Get the cached session types.
   *
   * @return PaperTypeApi[]
   */
  public static function getPaperTypes() {
    return self::get(self::$namePaperTypesCache, PaperTypeApi::class);
  }

  /**
   * Get the cached equipment.
   *
   * @return EquipmentApi[]
   */
  public static function getEquipment() {
    return self::get(self::$nameEquipmentCache, EquipmentApi::class);
  }

  /**
   * Get the cached volunteering options.
   *
   * @return VolunteeringApi[]
   */
  public static function getVolunteering() {
    return self::get(self::$nameVolunteeringCache, VolunteeringApi::class);
  }

  /**
   * Get the cached review criteria.
   *
   * @return ReviewCriteriaApi[]
   */
  public static function getReviewCriteria() {
    return self::get(self::$nameReviewCriteriaCache, ReviewCriteriaApi::class);
  }

  /**
   * Get the cached settings.
   *
   * @return array
   */
  public static function getSettings() {
    if ($result = \Drupal::cache()->get(self::$nameSettingsCache)) {
      return $result->data;
    }
    else {
      return self::setSettings();
    }
  }

  /**
   * Get the cached translations.
   *
   * @return array
   */
  public static function getTranslations() {
    if ($result = \Drupal::cache()->get(self::$nameTranslationsCache)) {
      return $result->data;
    }
    else {
      return self::setTranslations();
    }
  }

  /**
   * Get the cached session keys and values.
   *
   * @return array
   */
  public static function getSessionsKeyValue() {
    if ($result = \Drupal::cache()->get(self::$nameSessionsKeyValueCache)) {
      return $result->data;
    }
    else {
      return self::setSessionsKeyValue();
    }
  }

  /**
   * Default set cache implementation.
   *
   * @param string $cacheName The name of the cache.
   * @param CRUDApiClient $apiClass Instance of the API class to cache.
   *
   * @return mixed|null The results that were cached.
   */
  private static function set($cacheName, $apiClass) {
    ConferenceApiClient::setYearCode(NULL);

    $results = $apiClass::getListWithCriteria(array());
    if ($results != NULL) {
      \Drupal::cache()->set($cacheName, $results->getResults());
      return $results->getResults();
    }

    return NULL;
  }

  /**
   * Default get cache implementation.
   *
   * @param string $cacheName The name of the cache.
   * @param CRUDApiClient $apiClass Instance of the API class to cache.
   *
   * @return mixed|null The results that were cached.
   */
  private static function get($cacheName, $apiClass) {
    if ($result = \Drupal::cache()->get($cacheName)) {
      return $result->data;
    }
    else {
      return self::set($cacheName, $apiClass);
    }
  }
}