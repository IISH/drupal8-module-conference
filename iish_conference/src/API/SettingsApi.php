<?php
namespace Drupal\iish_conference\API;

use Drupal\iish_conference\ConferenceMisc;

/**
 * API that returns settings set in the CMS
 */
class SettingsApi {
  const ALLOWED_PAPER_EXTENSIONS = 'allowed_paper_extensions';
  const AUTHOR_REGISTRATION_LASTDATE = 'author_registration_lastdate';
  const AWARD_NAME = 'award_name';
  const AWARD_URI = 'award_uri';
  const BANK_TRANSFER_LASTDATE = 'bank_transfer_lastdate';
  const BANK_TRANSFER_INFO = 'bank_transfer_info';
  const BANK_TRANSFER_ALLOWED = 'bank_transfer_allowed';
  const CONFERENCE_CODE = 'conference_code';
  const COUNTRY_ID = 'country_id';
  const DEFAULT_NETWORK_ID = 'default_network_id';
  const DEFAULT_ORGANISATION_EMAIL = 'default_organisation_email';
  const DOWNLOAD_PAPER_LASTDATE = 'download_paper_lastdate';
  const EMAIL_MAX_NUM_TRIES = 'email_max_num_tries';
  const FINAL_REGISTRATION_LASTDATE = 'final_registration_lastdate';
  const GENERAL_TERMS_CONDITIONS_LINK = 'general_terms_conditions_link';
  const KEYWORD_NAME_PLURAL = 'keyword_name_plural';
  const KEYWORD_NAME_SINGULAR = 'keyword_name_singular';
  const MAX_PAPERS_PER_PERSON_PER_SESSION = 'max_papers_per_person_per_session';
  const MAX_UPLOAD_SIZE_PAPER = 'max_upload_size_paper';
  const NETWORK_NAME_PLURAL = 'network_name_plural';
  const NETWORK_NAME_SINGULAR = 'network_name_singular';
  const NUM_CANDIDATE_VOTES_ADVISORY_BOARD = 'num_candidate_votes_advisory_board';
  const NUM_PAPER_KEYWORDS_FREE = 'num_paper_keywords_free';
  const NUM_PAPER_KEYWORDS_FROM_LIST = 'num_paper_keywords_from_list';
  const ONLINE_PROGRAM_HEADER = 'online_program_header';
  const ONLINE_PROGRAM_HEADER_PAST_CONFERENCE = 'online_program_header_past_conference';
  const ONLINE_PROGRAM_UNDER_CONSTRUCTION = 'online_program_under_construction';
  const ON_SITE_PAYMENT_INFO = 'on_site_payment_info';
  const ORGANIZER_REGISTRATION_LASTDATE = 'organizer_registration_lastdate';
  const PARTICIPANT_TYPES_COMBINATION_INFO = 'participant_types_combination_info';
  const PATH_FOR_ADMIN_MENU = 'path_for_admin_menu';
  const PATH_FOR_MENU = 'path_for_menu';
  const PAYMENT_ON_SITE_STARTDATE = 'payment_on_site_startdate';
  const PREREGISTRATION_LASTDATE = 'preregistration_lastdate';
  const PREREGISTRATION_LASTDATE_MESSAGE = 'preregistration_lastdate_message';
  const PREREGISTRATION_STARTDATE = 'preregistration_startdate';
  const PREREGISTRATION_SESSIONS = 'preregistration_sessions';
  const SESSION_NAME_PLURAL = 'session_name_plural';
  const SESSION_NAME_SINGULAR = 'session_name_singular';
  const URL_PRIVACY_STATEMENT = 'url_privacy_statement';

  // Show / hide
  const ALLOW_NETWORK_CHAIRS_TO_SEE_ALL_NETWORKS = 'allow_network_chairs_to_see_all_networks';
  const HIDE_ALWAYS_IN_ONLINE_PROGRAMME = 'hide_always_in_online_programme';
  const HIDE_IF_EMPTY_IN_ONLINE_PROGRAMME = 'hide_if_empty_in_online_programme';
  const SHOW_ACCOMPANYING_PERSONS = 'show_accompanying_persons';
  const SHOW_AGE_RANGE = 'show_age_range';
  const SHOW_AUTHOR_REGISTRATION = 'show_author_registration';
  const SHOW_AWARD = 'show_award';
  const SHOW_CHAIR_DISCUSSANT_POOL = 'show_chair_discussant_pool';
  const SHOW_CV = 'show_cv';
  const SHOW_DAYS = 'show_days';
  const SHOW_DAYS_SESSION_PLANNED = 'show_days_session_planned';
  const SHOW_DEPARTMENT = 'show_department';
  const SHOW_DIETARY_WISHES = 'show_dietary_wishes';
  const SHOW_EDUCATION = 'show_education';
  const SHOW_EQUIPMENT = 'show_equipment';
  const SHOW_FINISH_LATER_BUTTON = 'show_finish_later_button';
  const SHOW_GENERAL_COMMENTS = 'show_general_comments';
  const SHOW_LANGUAGE_COACH_PUPIL = 'show_language_coach_pupil';
  const SHOW_NETWORK = 'show_network';
  const SHOW_NETWORK_CHAIRS = 'show_network_chairs';
  const SHOW_NETWORK_PARTICIPANT_STATE = 'show_network_participant_state';
  const SHOW_OPT_IN = 'show_opt_in';
  const SHOW_ORGANIZER_REGISTRATION = 'show_organizer_registration';
  const SHOW_PAPER_TYPES= 'show_paper_types';
  const SHOW_OPTIONAL_PAPER_TYPE = 'show_optional_paper_type';
  const SHOW_PROGRAMME_ONLINE = 'show_programme_online';
  const SHOW_SESSION_CODES = 'show_session_codes';
  const SHOW_SESSION_ENDTIME_IN_PP = 'show_session_endtime_in_pp';
  const SHOW_SESSION_EXTRA_INFO = 'show_session_extra_info';
  const SHOW_SESSION_PARTICIPANT_TYPES_REGISTRATION = 'show_session_participant_types_registration';
  const SHOW_SESSION_PROPOSAL = 'show_session_proposal';
  const SHOW_SESSION_TYPES = 'show_session_types';
  const SHOW_OPTIONAL_SESSION_TYPE = 'show_optional_session_type';
  const SHOW_STUDENT = 'show_student';
  const SHOW_PAPER_ABSTRACT_IN_ONLINE_PROGRAMME = 'show_paper_abstract_in_online_programme';
  const SHOW_SESSION_ABSTRACT_IN_ONLINE_PROGRAMME = 'show_session_abstract_in_online_programme';
  const SHOW_SESSION_NUMBER_IN_ONLINE_PROGRAMME = 'show_session_number_in_online_programme';
  const SHOW_ROOM_NAME_IN_ONLINE_PROGRAMME = 'show_room_name_in_online_programme';
  const SHOW_PRIVACY_STATEMENT_ON_PERSONAL_PAGE = 'show_privacy_statement_on_personal_page';
  const SHOW_PRIVACY_STATEMENT_ON_REGISTRATION_PAGE = 'show_privacy_statement_on_registration_page';

  // Required fields
  const REQUIRED_CV = 'required_cv';
  const REQUIRED_PAPER_UPLOAD = 'required_paper_upload';

  // Email templates
  const BANK_TRANSFER_EMAIL_TEMPLATE_ID = 'bank_transfer_email_template_id';
  const PAYMENT_ACCEPTED_EMAIL_TEMPLATE_ID = 'payment_accepted_email_template_id';
  const PAYMENT_ON_SITE_EMAIL_TEMPLATE_ID = 'payment_on_site_email_template_id';
  const PRE_REGISTRATION_EMAIL_TEMPLATE_ID = 'pre_registration_email_template_id';

  // PayWay
  const PAYWAY_ADDRESS = 'payway_address';
  const PAYWAY_PASSPHRASE_IN = 'payway_passphrase_in';
  const PAYWAY_PASSPHRASE_OUT = 'payway_passphrase_out';
  const PAYWAY_PROJECT = 'payway_project';

  private $client;
  private static $apiName = 'settings';
  private static $cachedSettings;

  public function __construct() {
    $this->client = new ConferenceApiClient();
  }

  /**
   * Returns an array with the CMS settings where the keys hold the property
   *
   * @return array|null The settings array or null in case of a failure
   */
  public function settings() {
    return $this->client->get(self::$apiName, array());
  }

  /**
   * Recommended use for obtaining the value for a certain setting.
   * The settings array is obtained from the cache and the value for the given property is returned (if it exists)
   *
   * @param string $property The name of the property
   * @param string $type What type to return
   *
   * @return mixed The value set for this property for this event, or null if not found
   */
  public static function getSetting($property, $type = 'string') {
    if (!is_array(self::$cachedSettings)) {
      self::$cachedSettings = CachedConferenceApi::getSettings();
    }

    $setting = NULL;
    if (is_array(self::$cachedSettings) && isset(self::$cachedSettings[$property])) {
      $setting = self::$cachedSettings[$property];
    }

    switch ($type) {
      case 'list':
        return is_string($setting) ? explode(';', $setting) : array();
      case 'map':
        $map = array();
        $list = is_string($setting) ? explode(';', $setting) : array();
        foreach ($list as $item) {
          $keyValue = explode(':', $item);
          $map[$keyValue[0]] = $keyValue[1];
        }
        return $map;
      case 'time':
        return is_string($setting) ? strtotime($setting) : NULL;
      case 'bool':
        return $setting == 1;
      case 'lastdate':
        return is_string($setting) ? ConferenceMisc::isOpenForLastDate(strtotime($setting)) : false;
      case 'startdate':
        return is_string($setting) ? ConferenceMisc::isOpenForStartDate(strtotime($setting)) : false;
      case 'string':
      default:
        return $setting;
    }
  }
}
