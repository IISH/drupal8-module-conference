<?php
namespace Drupal\iish_conference;

use Drupal\Component\Render\MarkupInterface;
use Drupal\iish_conference\API\SettingsApi;
use Drupal\iish_conference\Markup\ConferenceHTML;

/**
 * Twig extensions used by the conference modules.
 */
class TwigExtension extends \Twig_Extension {

  /**
   * This Twig extensions name.
   */
  public function getName() {
    return 'iish_conference.twig_extension';
  }

  /**
   * Returns a list of filters to add to the existing list.
   * @return \Twig_SimpleFilter[] An array of filters
   */
  public function getFilters() {
    return array(
      new \Twig_SimpleFilter('iish_t', 'iish_t', array('is_safe' => array('html'))),

      new \Twig_SimpleFilter('text', array(
        $this,
        'text'
      ), array('is_safe' => array('html'))),

      new \Twig_SimpleFilter('markup', array(
        $this,
        'markup'
      ), array('is_safe' => array('html'))),

      new \Twig_SimpleFilter('long', array(
        $this,
        'long'
      ), array('is_safe' => array('html'))),
    );
  }

  /**
   * Returns a list of functions to add to the existing list.
   * @return \Twig_SimpleFunction[] An array of functions
   */
  public function getFunctions() {
    return array(
      new \Twig_SimpleFunction('setting', array(
        $this,
        'setting'
      )),
    );
  }

  /**
   * Escapes text.
   * @param mixed $text The text to be escaped.
   * @return MarkupInterface The escaped text.
   */
  public function text($text) {
    if ($text instanceof MarkupInterface) {
      return $text;
    }
    return new ConferenceHTML($text, FALSE);
  }

  /**
   * Escapes HTML.
   * @param mixed $text The text to be escaped.
   * @return MarkupInterface The escaped text.
   */
  public function markup($text) {
    if ($text instanceof MarkupInterface) {
      return $text;
    }
    return new ConferenceHTML($text, TRUE);
  }

  /**
   * Returns HTML for long text.
   * @param string $text The long text.
   * @return ConferenceHTML The text as HTML.
   */
  public function long($text) {
    return ConferenceMisc::getHTMLForLongText($text);
  }

  /**
   * Returns the value of a setting.
   * @param string $setting The name of the setting.
   * @param string $type What type to return.
   * @return mixed The value for the given setting.
   */
  public function setting($setting, $type = 'string') {
    return SettingsApi::getSetting(strtolower($setting), $type);
  }
}
