<?php
namespace Drupal\iish_conference\Markup;

use Drupal\Component\Render\MarkupInterface;

/**
 * Email markup.
 * TODO: Make sure the JavaScript is no longer escaped.
 */
class Email implements MarkupInterface {
  private $email;
  private $label;

  /**
   * Protects again email harvesting by cutting up the email address into pieces.
   *
   * @param string $email The email address in question
   * @param string $label The label of the email address
   */
  public function __construct($email, $label = '') {
    $this->email = trim($email);
    $this->label = trim($label);
  }

  /**
   * Returns markup.
   *
   * @return string
   *   The markup.
   */
  public function __toString() {
    if ($this->label != '') {
      $ret = "<span><script language=\"javascript\" type=\"text/javascript\">
<!--
var w = \"::NAME::\";
var h1 = \"::DOMAIN1::\";
var h2 = \"::DOMAIN2::\";
var l = \"::LABEL::\";
document.write('<a hr'+'ef=\"'+'mai'+'lto:'+w+'@'+h1+'.'+h2+'\">'+l+'<\/a>');
//-->
</script>";
    }
    else {
      $ret = "<script language=\"javascript\" type=\"text/javascript\">
<!--
var w = \"::NAME::\";
var h1 = \"::DOMAIN1::\";
var h2 = \"::DOMAIN2::\";
document.write('<a hr'+'ef=\"'+'mai'+'lto:'+w+'@'+h1+'.'+h2+'\">'+w+'@'+h1+'.'+h2+'<\/a>');
//-->
</script></span>";
    }

    // divide @
    $arr = explode('@', $this->email);
    $preAt = $arr[0];
    $postAt = $arr[1];

    // divide first dot
    $pos = strpos($postAt, '.');
    $domain1 = substr($postAt, 0, $pos);
    $domain2 = substr($postAt, -(strlen($postAt) - $pos - 1));

    // place in template
    $ret = str_replace('::NAME::', $preAt, $ret);
    $ret = str_replace('::DOMAIN1::', $domain1, $ret);
    $ret = str_replace('::DOMAIN2::', $domain2, $ret);
    $ret = str_replace('::LABEL::', $this->label, $ret);

    return $ret;
  }

  /**
   * No JSON serialization
   */
  function jsonSerialize() {
    return NULL;
  }
}