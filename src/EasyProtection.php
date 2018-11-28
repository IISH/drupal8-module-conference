<?php
namespace Drupal\iish_conference;

/**
 * Helper methods to help protect integers and strings and only return the intended result
 */
class EasyProtection {
  private static $removeString = array(
    '\'',
    '"',
    '<',
    '>',
    "\r",
    "\n",
    ':',
    ';',
    '{',
    '}',
    '[',
    ']',
    '!',
    '?',
    '|',
  );

  /**
   * Tries to remove all unwanted characters and only return the integer
   *
   * @param string $text The text to obtain the integer from
   * @param bool $allowNegative Whether to allow negative integers
   * @param int $length The maximum character length of the integer
   *
   * @return int|null The integer
   */
  public static function easyIntegerProtection($text, $allowNegative = FALSE, $length = 6) {
    $text = $allowNegative ? preg_replace("/[^0-9-]/", ' ', $text) : preg_replace("/[^0-9]/", ' ', $text);
    $text = trim($text);
    $text = self::getLeftPart($text, ' ');
    $text = substr($text, 0, $length);

    return is_numeric($text) ? intval($text) : NULL;
  }

  /**
   * Tries to remove all unwanted characters from a string
   *
   * @param string $text The text in question
   *
   * @return string The result
   */
  public static function easyStringProtection($text) {
    $text = str_replace(self::$removeString, ' ', $text);
    $text = trim($text);

    while (strpos($text, '  ') !== FALSE) {
      $text = str_replace('  ', ' ', $text);
    }

    return $text;
  }

  /**
   * Removes all characters from a string besides alphanumeric characters, spaces and additional given characters
   *
   * @param string $text The text in question
   * @param string $extraChars The extra characters that have to remain, regex valid
   *
   * @return string The filtered text, with only alphanumeric characters and additional given characters
   */
  public static function easyAlphaNumericStringProtection($text, $extraChars = '') {
    $text = preg_replace('/[^a-zA-Z0-9\s' . $extraChars . ']/', '', $text);
    $text = trim($text);

    return $text;
  }

  /**
   * Returns the most left part of the supplied text if split with the supplied search string
   *
   * @param string $text The text to obtain the most left part of
   * @param string $search The text to search for in order to obtain the most left part
   *
   * @return string The left part of the text
   */
  private static function getLeftPart($text, $search = ' ') {
    $pos = strpos($text, $search);
    if ($pos !== FALSE) {
      $text = substr($text, 0, $pos);
    }

    return $text;
  }
} 