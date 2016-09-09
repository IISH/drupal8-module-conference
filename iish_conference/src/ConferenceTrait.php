<?php
namespace Drupal\iish_conference;

use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Form\FormStateInterface;

use Drupal\iish_conference\API\EmptyApi;
use Drupal\iish_conference\API\SettingsApi;
use Drupal\iish_conference\API\CRUDApiClient;
use Drupal\iish_conference\API\Domain\NetworkApi;
use Drupal\iish_conference\API\LoggedInUserDetails;
use Drupal\iish_conference\API\CachedConferenceApi;

use Drupal\iish_conference\Markup\ConferenceHTML;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Trait for conference controllers and forms.
 */
trait ConferenceTrait {

  /**
   * Check if a conference user is logged in. If not, redirect to the login page (if enabled).
   * @return bool|RedirectResponse Returns a redirect response on a redirect, FALSE otherwise.
   */
  public function redirectIfNotLoggedIn() {
    if (!LoggedInUserDetails::isLoggedIn()) {
      $url = Url::fromRoute('<front>');
      if (\Drupal::moduleHandler()->moduleExists('iish_conference_login_logout')) {
        $url = Url::fromRoute(
          'iish_conference_login_logout.login_form',
          array(),
          array('query' => \Drupal::destination()->getAsArray())
        );
      }

      return new RedirectResponse($url->toString());
    }

    return FALSE;
  }

  /**
   * Check if a conference user is logged in and an admin. If not logged in, redirect to the login page (if enabled).
   * If logged in, but not an admin, show 'access denied' message.
   * @return bool|RedirectResponse|array Returns a redirect response on a redirect,
   * a render array on access denied, FALSE otherwise.
   */
  public function checkAdmin() {
    if ($response = self::redirectIfNotLoggedIn() !== FALSE) {
      return $response;
    }

    if (!LoggedInUserDetails::isCrew() && !LoggedInUserDetails::hasFullRights()) {
      if (\Drupal::moduleHandler()->moduleExists('iish_conference_login_logout')) {
        $link = Link::fromTextAndUrl(
          iish_t('log out and login'),
          Url::fromRoute(
            'iish_conference_login_logout.login_form',
            array(),
            array('query' => \Drupal::destination()->getAsArray())
          )
        );

        drupal_set_message(new ConferenceHTML(
          iish_t('Access denied.') . '<br>' .
          iish_t('Current user ( @user ) is not a conference crew member.',
            array('@user' => LoggedInUserDetails::getUser())) . '<br>' .
          iish_t('Please @login as a crew member.',
            array('@login' => $link->toString())), TRUE
        ), 'error');
      }
      else {
        drupal_set_message(iish_t('Access denied.') . '<br>' .
          iish_t('Current user ( @user ) is not a conference crew member.',
            array('@user' => LoggedInUserDetails::getUser())), 'error');
      }

      return array();
    }

    return FALSE;
  }

  /**
   * Check if a conference user is logged in and a network chair. If not logged in,
   * redirect to the login page (if enabled). If logged in, but not a network chair, show 'access denied' message.
   * @return bool|RedirectResponse|array Returns a redirect response on a redirect,
   * a render array on access denied, FALSE otherwise.
   */
  public function checkNetworkChair() {
    if ($response = self::redirectIfNotLoggedIn() !== FALSE) {
      return $response;
    }

    if (!LoggedInUserDetails::isCrew() && !LoggedInUserDetails::isNetworkChair()) {
      drupal_set_message(iish_t('Access denied. You are not a chair of a network.'), 'error');

      return array();
    }

    return FALSE;
  }

  /**
   * Redirects to the personal page if enabled, otherwise the homepage.
   * @return RedirectResponse Redirect response.
   */
  public function redirectToPersonalPage() {
    if (\Drupal::moduleHandler()->moduleExists('iish_conference_personalpage')) {
      return new RedirectResponse(Url::fromRoute('iish_conference_personalpage.index'));
    }

    return new RedirectResponse(Url::fromRoute('<front>'));
  }

  /**
   * Redirects to the personal page if enabled, otherwise the homepage from a form submit state.
   * @param FormStateInterface $form_state The form state.
   */
  public function formRedirectToPersonalPage(FormStateInterface $form_state) {
    if (\Drupal::moduleHandler()->moduleExists('iish_conference_personalpage')) {
      $form_state->setRedirect('iish_conference_personalpage.index');
    }
    else {
      $form_state->setRedirect('<front>');
    }
  }

  /**
   * Create a render array that will render a link back to the personal page.
   * @param string $fragment The fragment on the personal page to link back to.
   * @return array The render array.
   */
  public function backToPersonalPageLink($fragment = 'links') {
    if (\Drupal::moduleHandler()->moduleExists('iish_conference_personalpage')) {
      $url = Url::fromRoute('iish_conference_personalpage.index',
        array(), array('fragment' => $fragment));

      return array(
        '#markup' => '<div class="bottommargin">' . Link::fromTextAndUrl(
          '« ' . iish_t('Go back to your personal page'), $url)->toString() .
          '</div>'
      );
    }

    return array();
  }

  /**
   * Returns a list of all networks that this conference user is allowed to see.
   * @return NetworkApi[] A list of networks.
   */
  public function getAllowedNetworks() {
    $networks = CachedConferenceApi::getNetworks();
    if ((SettingsApi::getSetting(SettingsApi::ALLOW_NETWORK_CHAIRS_TO_SEE_ALL_NETWORKS) <> 1)
      && !LoggedInUserDetails::isCrew()
    ) {
      return NetworkApi::getOnlyNetworksOfChair($networks, LoggedInUserDetails::getUser());
    }
    return $networks;
  }

  /**
   * Create a render array that will render a list of links.
   * @param string $title The title of the list.
   * @param string $class A class name to add to the HTML.
   * @param CRUDApiClient[] $entities The list of entities to transform into links.
   * @param string $suffix A suffix to add to each of the links in the list.
   * @param string $route The route to render the links.
   * @param string $paramName The name of the parameter to include the entity id with.
   * @return array The render array.
   */
  public function getLinks($title, $class, $entities, $suffix, $route, $paramName) {
    $links = array();
    foreach ($entities as $entity) {
      $links[] = array(
        array('#markup' => Link::fromTextAndUrl($entity->__toString(),
          Url::fromRoute($route, array($paramName => $entity->getId())))
          ->toString()),
        array('#markup' => ' ' . $suffix)
      );
    }

    return array(
      '#theme' => 'item_list',
      '#type' => 'ol',
      '#title' => $title,
      '#attributes' => array('class' => $class),
      '#items' => $links,
    );
  }

  /**
   * Create a render array that will render a navigation menu.
   * @param CRUDApiClient[] $list The list of entities to navigate through.
   * @param CRUDApiClient $cur The current entity from the list.
   * @param string $backText The text to use on the link back to the parent page.
   * @param Url $backUrl The base URL for the parent page.
   * @param Url $prevNextUrl The base URL for the prev/next page.
   * @param string $paramName The name of the parameter to include the entity id with.
   * @return array The render array.
   */
  public function getNavigation($list, $cur, $backText, $backUrl, $prevNextUrl, $paramName) {
    $renderArray = array(
      '#theme' => 'iish_conference_navigation',
      '#prevLink' => Link::fromTextAndUrl($backText, $backUrl),
    );

    $cur = ($cur === NULL) ? new EmptyApi() : $cur;

    $prevNext = CRUDApiClient::getPrevNext($list, $cur);
    if ($prevNext[0] !== NULL) {
      $prevUrl = clone $prevNextUrl;
      $prevUrl->setRouteParameter($paramName, $prevNext[0]->getId());
      $renderArray['#prev'] = $prevUrl;
    }
    if ($prevNext[1] !== NULL) {
      $nextUrl = clone $prevNextUrl;
      $nextUrl->setRouteParameter($paramName, $prevNext[1]->getId());
      $renderArray['#next'] = $nextUrl;
    }

    return $renderArray;
  }

  /**
   * Renders an Excel download response.
   * @param string $excelData The Excel file.
   * @param string $fileName The name of the downloaded file.
   * @return Response The download response.
   */
  public function getExcelResponse($excelData, $fileName) {
    return new Response(
      $excelData,
      Response::HTTP_OK,
      array(
        'Content-Type' => 'application/vnd.ms-excel',
        'Pragma' => 'public',
        'Expires' => 0,
        'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
        'Content-Disposition' => 'attachment; filename="' . $fileName . '";',
        'Content-Transfer-Encoding' => 'binary',
        'Content-Length' => strlen($excelData),
      )
    );
  }
}
