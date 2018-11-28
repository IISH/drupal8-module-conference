<?php
namespace Drupal\iish_conference\Plugin\Block;

use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Block\BlockBase;

use Drupal\iish_conference\API\LoggedInUserDetails;

/**
 * Provides a conference user block.
 *
 * @Block(
 *   id = "iish_conference_userblock",
 *   admin_label = @Translation("User logged in?"),
 *   category = @Translation("IISH Conference")
 * )
 */
class UserBlock extends BlockBase {

  /**
   * Builds and returns the renderable array for this block plugin.
   *
   * If a block should not be rendered because it has no content, then this
   * method must also ensure to return no content: it must then only return an
   * empty array, or an empty array with #cache set (with cacheability metadata
   * indicating the circumstances for it being empty).
   *
   * @return array
   *   A renderable array representing the content of the block.
   *
   * @see \Drupal\block\BlockViewBuilder
   */
  public function build() {
    try {
      if (LoggedInUserDetails::isLoggedIn()) {
        $personalPageLink = Link::fromTextAndUrl(
          LoggedInUserDetails::getUser()->getFullName(),
          Url::fromRoute('iish_conference_personalpage.index')
        );

        $logoutLink = Link::fromTextAndUrl(
          iish_t('Log out'),
          Url::fromRoute('iish_conference_login_logout.logout_form')
        );

        $html = '<span class="iishconference-userblock">'
          . iish_t('Welcome') . ' '
          . $personalPageLink->toString()
          . ' | '
          . $logoutLink->toString()
          . '</span>';
      }
      else {
        $loginLink = Link::fromTextAndUrl(
          iish_t('log in'),
          Url::fromRoute('iish_conference_login_logout.login_form')
        );

        $html = '<span class="iishconference-userblock">'
          . iish_t('Please @link.', array('@link' => $loginLink->toString()))
          . '</span>';
      }
    } catch (\Exception $exception) {
      $html = '<span class="iishconference-userblock"></span>';
    }

    return array(
      '#markup' => $html,
      '#cache' => array('max-age' => 0),
    );
  }
}