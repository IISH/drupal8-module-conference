<?php
namespace Drupal\iish_conference;

/**
 * Allows for highlighting text with the given needle(s)
 */
class Highlighter {
  private $needles;
  private $openingTag;
  private $closingTag;

  private static $normalizeTable = array(
    'à' => 'a',
    'á' => 'a',
    'â' => 'a',
    'ã' => 'a',
    'ä' => 'a',
    'å' => 'a',
    'æ' => 'a',
    'þ' => 'b',
    'ç' => 'c',
    'č' => 'c',
    'ć' => 'c',
    'è' => 'e',
    'é' => 'e',
    'ê' => 'e',
    'ë' => 'e',
    'ì' => 'i',
    'í' => 'i',
    'î' => 'i',
    'ï' => 'i',
    'ñ' => 'n',
    'ð' => 'o',
    'ò' => 'o',
    'ó' => 'o',
    'ô' => 'o',
    'õ' => 'o',
    'ö' => 'o',
    'ø' => 'o',
    'ŕ' => 'r',
    'š' => 's',
    'ù' => 'u',
    'ú' => 'u',
    'û' => 'u',
    'ý' => 'y',
    'ÿ' => 'y',
    'ž' => 'z',
  );

  /**
   * An array of needles, or a single needle, to highlight
   *
   * @param array|string $needles The needle(s)
   */
  public function __construct($needles) {
    $this->needles = array();

    if (is_array($needles)) {
      foreach ($needles as $needle) {
        if (mb_strlen(trim($needle)) > 0) {
          $this->needles[] = self::normalize(mb_strtolower(trim($needle)));
        }
      }
    }
    else {
      if (mb_strlen(trim($needles)) > 0) {
        $this->needles[] = self::normalize(mb_strtolower(trim($needles)));
      }
    }
  }

  /**
   * Set the closing tag for the area to be highlighted
   *
   * @param string $closingTag The HTML closing tag
   */
  public function setClosingTag($closingTag) {
    $this->closingTag = $closingTag;
  }

  /**
   * Get the closing tag for the area to be highlighted
   *
   * @return string The HTML closing tag
   */
  public function getClosingTag() {
    return $this->closingTag;
  }

  /**
   * Set the opening tag for the area to be highlighted
   *
   * @param string $openingTag The HTML opening tag
   */
  public function setOpeningTag($openingTag) {
    $this->openingTag = $openingTag;
  }

  /**
   * Get the opening tag for the area to be highlighted
   *
   * @return string The HTML opening tag
   */
  public function getOpeningTag() {
    return $this->openingTag;
  }

  /**
   * Highlight the given haystack with the needles provided earlier
   *
   * @param string $haystack The haystack of which to highlight the needles
   *
   * @return string The haystack, but now with highlighting
   */
  public function highlight($haystack) {
    $lwHaystack = self::normalize(mb_strtolower($haystack));
    $positions = array();

    // Look up all the needle positions
    foreach ($this->needles as $needle) {
      $offset = 0;
      while (($position = mb_strpos($lwHaystack, $needle, $offset)) !== FALSE) {
        $end = $position + mb_strlen($needle);
        $positions[] = array('start' => $position, 'end' => $end);
        $offset = $position + 1;
      }
    }

    // Now start highlighting areas
    $start = NULL;
    $end = NULL;
    $extraLength = 0;
    sort($positions);
    foreach ($positions as $position) {
      if ($start === NULL) {
        $start = $position['start'];
        $end = $position['end'];
      }
      else {
        if (($position['start'] <= $end) && ($position['end'] >= $end)) {
          // Overlapping areas, combine them
          $end = $position['end'];
        }
        else {
          if ($position['start'] > $end) {
            // New area found, highlight the previous area and continue with new one
            $haystack = mb_substr($haystack, 0, $start + $extraLength) . $this->openingTag . mb_substr($haystack, $start + $extraLength);
            $extraLength += mb_strlen($this->openingTag);

            $haystack = mb_substr($haystack, 0, $end + $extraLength) . $this->closingTag . mb_substr($haystack, $end + $extraLength);
            $extraLength += mb_strlen($this->closingTag);

            $start = $position['start'];
            $end = $position['end'];
          }
        }
      }
    }

    // Highlight the latest found area
    if (($start !== NULL) && ($end !== NULL)) {
      $haystack = mb_substr($haystack, 0, $start + $extraLength) . $this->openingTag . mb_substr($haystack, $start + $extraLength);
      $extraLength += mb_strlen($this->openingTag);
      $haystack = mb_substr($haystack, 0, $end + $extraLength) . $this->closingTag . mb_substr($haystack, $end + $extraLength);
    }

    return $haystack;
  }

  /**
   * Very simple normalizing method of text, single characters only
   *
   * @param $text
   *
   * @return string The normalized text
   */
  private static function normalize($text) {
    return strtr($text, self::$normalizeTable);
  }
}
