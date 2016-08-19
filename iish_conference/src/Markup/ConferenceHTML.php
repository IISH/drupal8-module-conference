<?php
namespace Drupal\iish_conference\Markup;

use Drupal\Component\Utility\Xss;
use Drupal\Component\Utility\Html;
use Drupal\Component\Render\MarkupInterface;

module_load_include('module', 'filter');

/**
 * Conference HTML
 */
class ConferenceHTML implements MarkupInterface {
  private $html;

  /**
   * Returns clean HTML to be used for output:
   * - The given text is trimmed
   * - If the text is empty, a '-' is displayed instead
   * - Web pages, emails and other URLs are translated to links
   * - If the text is HTML ($isHTML), the HTML is filtered
   * - If the text is plain text ($isHTML), all found HTML is escaped
   * - New lines (/n) are translated to HTML breaks (<br />)
   *
   * @param string $text The text to clean
   * @param bool $isHTML Whether the given text includes HTML
   */
  public function __construct($text, $isHTML = FALSE) {
    $filter = new \stdClass();
    $filter->callback = '_filter_url';
    $filter->settings = array('filter_url_length' => 300);

    $text = (($text === NULL) || (strlen(trim($text)) === 0)) ? '-' : trim($text);
    $text = ($isHTML) ? Xss::filterAdmin($text) : Html::escape($text);

    $this->html = _filter_url(nl2br($text), $filter);
  }

  /**
   * Returns markup.
   *
   * @return string
   *   The markup.
   */
  public function __toString() {
    return $this->html;
  }

  /**
   * No JSON serialization
   */
  function jsonSerialize() {
    return NULL;
  }
}