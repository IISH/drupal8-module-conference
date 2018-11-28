<?php
namespace Drupal\iish_conference\API;

/**
 * API that returns translations set in the CMS
 */
class TranslationsApi {
  private $client;
  private static $apiName = 'translations';
  private static $cachedTranslations;

  public function __construct() {
    $this->client = new ConferenceApiClient();
  }

  /**
   * Returns an array with the CMS translations where the keys hold the md5 of the original text
   *
   * @return array|null The translations array or null in case of a failure
   */
  public function translations() {
    return $this->client->get(self::$apiName, array());
  }

  /**
   * Recommended use for obtaining the translation of a given text.
   * The translations array is obtained from the cache and the translation is returned, if found.
   *
   * @param string $text The original text
   *
   * @return string The translation text, or the original text if the translation could not be found
   */
  public static function getTranslation($text) {
    if (!is_array(self::$cachedTranslations)) {
      self::$cachedTranslations = CachedConferenceApi::getTranslations();
    }

    $textMD5 = md5($text);
    if (is_array(self::$cachedTranslations) && isset(self::$cachedTranslations[$textMD5])) {
      return self::$cachedTranslations[$textMD5];
    }
    else {
      return $text;
    }
  }
} 