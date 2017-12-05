<?php
namespace Drupal\iish_conference_network_forchairs\Controller;

use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Controller\ControllerBase;

use Drupal\iish_conference\Highlighter;
use Drupal\iish_conference\ConferenceMisc;
use Drupal\iish_conference\EasyProtection;
use Drupal\iish_conference\ConferenceTrait;

use Drupal\iish_conference\API\EmptyApi;
use Drupal\iish_conference\API\SettingsApi;
use Drupal\iish_conference\API\ApiCriteriaBuilder;

use Drupal\iish_conference\API\Domain\NetworkApi;
use Drupal\iish_conference\API\Domain\SessionApi;

use Drupal\iish_conference\Markup\ConferenceHTML;

use Drupal\iish_conference_network_forchairs\API\SessionsSearchApi;
use Drupal\iish_conference_network_forchairs\API\ParticipantsInSessionApi;

use Symfony\Component\HttpFoundation\Response;

/**
 * The controller for the information for network chairs.
 */
class ForChairsController extends ControllerBase {
  use ConferenceTrait;

  /**
   * List all networks.
   * @return array|string|Response Render array or a redirect response.
   */
  public function listNetworks() {
    $this->checkNetworkChair();

    $search = $this->getSearch();
    if ($search !== NULL) {
      return $this->redirect('iish_conference_network_forchairs.network', array(
        'network' => '-1',
      ), array(
        'query' => array('search' => $search)
      ));
    }

    $networks = $this->getAllowedNetworks();
    if (count($networks) > 0) {
      return array(
        $this->backToPersonalPageLink('nclinks'),

        array(
          '#markup' => '<form action="" method="get" accept-charset="UTF-8">'
            . '<div class="form-item form-type-textfield form-item-search">'
            . '<label for="edit-search">'
            . iish_t('Search in session name')
            . '</label>'
            . '<input type="text" id="edit-search" name="search" value="'
            . (($search !== NULL) ? $search : '')
            . '" size="20" maxlength="50" class="form-text">'
            . '</div>'
            . '</form>',
          '#allowed_tags' => array('form', 'div', 'label', 'input')
        ),

        $this->getLinks(
          iish_t('Networks'), 'networksforchairs',
          $networks, '',
          'iish_conference_network_forchairs.network', 'network'
        ),
      );
    }
    else {
      drupal_set_message(iish_t('No networks found!'), 'warning');
      return array();
    }
  }

  /**
   * List the sessions for the given network.
   * @param NetworkApi $network The network.
   * @return array|string|Response The render array or redirect response.
   */
  public function network($network = NULL) {
    $this->checkNetworkChair();

    $fields = array();
    $search = $this->getSearch();
    $networkId = ($network !== NULL) ? $network->getId() : -1;

    $highlighter = NULL;
    if ($search !== NULL) {
      $search = EasyProtection::easyStringProtection($search);

      $highlighter = new Highlighter(explode(' ', $search));
      $highlighter->setOpeningTag('<span class="highlight">');
      $highlighter->setClosingTag('</span>');
    }
    else {
      if (!$network) {
        drupal_set_message(iish_t('The network does not exist.'), 'error');
        return $this->redirect('iish_conference_network_forchairs.index');
      }

      $fields[] = array(
        'label' => 'Network',
        'value' => $network->getName(),
      );

      if (SettingsApi::getSetting(SettingsApi::SHOW_NETWORK_CHAIRS, 'bool')) {
        $chairLinks = array();
        foreach ($network->getChairs() as $chair) {
          $chairLinks[] = Link::fromTextAndUrl(
            $chair->getFullName(),
            Url::fromUri('mailto:' . $chair->getEmail(), array('absolute' => TRUE))
          )->toString();
        }

        $fields[] = array(
          'label' => 'Chairs in this network',
          'value' => ConferenceMisc::getEnumSingleLine($chairLinks),
          'html' => TRUE,
        );
      }
    }

    if ($network !== NULL) {
      $props = new ApiCriteriaBuilder();
      $sessions = SessionApi::getListWithCriteria(
        $props
          ->eq('networks_id', $network->getId())
          ->sort('name', 'asc')
          ->get()
      )->getResults();
    }
    else {
      $sessionSearchApi = new SessionsSearchApi();
      $sessions = $sessionSearchApi->getSessions($search);
    }

    $links = array();
    foreach ($sessions as $session) {
      $name = $session->getName();
      if ($search && $highlighter) {
        $name = $highlighter->highlight($name);
      }

      $links[] = array(
        array('#markup' => Link::fromTextAndUrl(
          new ConferenceHTML($name, TRUE),
          Url::fromRoute('iish_conference_network_forchairs.session', array(
            'network' => $networkId,
            'session' => $session->getId()
          ), array(
            'query' => array('search' => $search),
          )))->toString()
        ),
        array('#markup' => ' <em>(' . $session->getState()->getSimpleDescription() . ')</em>')
      );
    }

    if ($network !== NULL) {
      $links[] = Link::fromTextAndUrl(
        iish_t('... Individual paper proposals ...'),
        Url::fromRoute('iish_conference_network_forchairs.session', array(
          'network' => $networkId,
          'session' => '-1'
        ))
      );
    }

    $hrLine = array();
    if (count($fields) > 0) {
      $hrLine = array(
        '#markup' => '<br /><hr /><br />'
      );
    }

    return array(
      $this->getNavigation(
        $this->getAllowedNetworks(),
        $network,
        '« ' . iish_t('Go back to networks list'),
        Url::fromRoute('iish_conference_network_forchairs.index'),
        Url::fromRoute('iish_conference_network_forchairs.network'),
        'network'
      ),

      array(
        '#theme' => 'iish_conference_container',
        '#styled' => FALSE,
        '#fields' => $fields,
      ),

      $hrLine,

      array(
        '#theme' => 'item_list',
        '#type' => 'ol',
        '#title' => iish_t('Sessions'),
        '#attributes' => array('class' => 'networksforchairs'),
        '#items' => $links,
      ),
    );
  }

  /**
   * List the papers for the given network and session.
   * @param NetworkApi $network The network.
   * @param SessionApi $session The session.
   * @return array|string|Response The render array or redirect response.
   */
  public function session($network = NULL, $session = NULL) {
    $this->checkNetworkChair();

    $search = $this->getSearch();
    $networkId = ($network !== NULL) ? $network->getId() : -1;
    $sessionId = ($session !== NULL) ? $session->getId() : -1;

    // Show error only if there is a network id given and the session does not belong in the network
    // or the network and/or session do not exist
    // Also show error when no network is chosen, but neither is a session search term
    if ($network && (!$network || ($session && !in_array($network->getId(), $session->getNetworksId())))) {
      drupal_set_message(iish_t('The network and/or session do not exist!'), 'error');
      return $this->redirect('iish_conference_network_forchairs.index');
    }

    if (!$network && ($search === NULL)) {
      drupal_set_message(iish_t('No network or search parameter given!'), 'error');
      return $this->redirect('iish_conference_network_forchairs.index');
    }

    if ($network) {
      $props = new ApiCriteriaBuilder();
      $sessions = SessionApi::getListWithCriteria(
        $props
          ->eq('networks_id', $network->getId())
          ->sort('name', 'asc')
          ->get()
      )->getResults();
      $sessions[] = new EmptyApi();
    }
    else {
      $sessionSearchApi = new SessionsSearchApi();
      $sessions = $sessionSearchApi->getSessions($search);
    }

    $fields = array();
    if ($network !== NULL) {
      $fields[] = array(
        'label' => 'Network',
        'value' => $network->getName(),
      );

      if (SettingsApi::getSetting(SettingsApi::SHOW_NETWORK_CHAIRS, 'bool')) {
        $chairLinks = array();
        foreach ($network->getChairs() as $chair) {
          $chairLinks[] = Link::fromTextAndUrl(
            $chair->getFullName(),
            Url::fromUri('mailto:' . $chair->getEmail(), array('absolute' => TRUE))
          )->toString();
        }

        $fields[] = array(
          'label' => 'Chairs in this network',
          'value' => ConferenceMisc::getEnumSingleLine($chairLinks),
          'html' => TRUE,
        );
      }
    }

    $fields[] = array(
      'label' => 'Session',
      'value' => ($session === NULL) ? iish_t('... Individual paper proposals ...') : $session->getName(),
    );

    if ($session !== NULL) {
      $fields[] = array(
        'label' => 'Session state',
        'value' => $session->getState()->getDescription(),
      );

      if ($session->getAddedBy() !== NULL) {
        $fields[] = array(
          'label' => 'Session added by',
          'value' => Link::fromTextAndUrl($session->getAddedBy()->getFullName(),
            Url::fromUri('mailto:' . $session->getAddedBy()->getEmail(),
              array('absolute' => TRUE)))->toString(),
          'html' => TRUE,
        );
      }

      $fields[] = array(
        'label' => 'Session abstract',
        'value' => $session->getAbstr(),
        'newLine' => TRUE,
      );
    }

    $participantsInSessionApi = new ParticipantsInSessionApi();
    $participantsInSession = $participantsInSessionApi->getParticipantsForSession($network, $session);

    $participantData = array();
    foreach ($participantsInSession as $i => $participant) {
      if ($i === 0) {
        $participantData[] = new ConferenceHTML('<br/><hr/><br/>', TRUE);
      }

      $user = $participant['user'];
      $paper = $participant['paper'];
      $type = $participant['type'];
      $participant_date = $participant['participantDate'];

      $participantData[] = array(
        'label' => 'Participant name',
        'value' => Link::fromTextAndUrl(
          $user->getFullName(),
          Url::fromUri('mailto:' . $user->getEmail(), array('absolute' => TRUE))
        )->toString(),
        'html' => TRUE,
      );

      if (SettingsApi::getSetting(SettingsApi::SHOW_NETWORK_PARTICIPANT_STATE, 'bool')) {
        $state_pre_style = (($participant_date->getStateId() == 0 || $participant_date->getStateId() == 999) ? '<span class="eca_warning">' : '');
        $state_post_style = (($participant_date->getStateId() == 0 || $participant_date->getStateId() == 999) ? '</span>' : '');

        $participantData[] = array(
          'label' => 'Participant state',
          'value' => $state_pre_style . SettingsApi::getSetting('participantstate' . $participant_date->getStateId()) . $state_post_style,
          'html' => TRUE,
        );
      }

      if (($user->getOrganisation() !== NULL) && (strlen($user->getOrganisation()) > 0)) {
        $participantData[] = array(
          'label' => 'Organisation',
          'value' => $user->getOrganisation(),
        );
      }

      if (SettingsApi::getSetting(SettingsApi::SHOW_CV, 'bool') && ($user->getCv() !== NULL) &&
        (strlen($user->getCv()) > 0)
      ) {
        $participantData[] = array(
          'label' => 'CV',
          'value' => $user->getCv(),
          'newLine' => TRUE,
        );
      }

      if ($type) {
        $participantData[] = array(
          'label' => 'Type',
          'value' => $type->getType(),
        );
      }

      // show if type is 'with paper' of if session id lower then 0 (individual paper)
      if ($paper && ($type && $type->getWithPaper() || $sessionId < 0)) {
        $participantData[] = new ConferenceHTML('<br>', TRUE);

        $participantData[] = array(
          'label' => 'Paper',
          'value' => $paper->getTitle(),
        );
        $participantData[] = array(
          'label' => 'Paper state',
          'value' => $paper->getState(),
        );
        $participantData[] = array(
          'label' => 'Paper abstract',
          'value' => $paper->getAbstr(),
          'newLine' => TRUE,
        );
      }

      $participantData[] = new ConferenceHTML('<br>', TRUE);
      if ($i < (count($participantsInSession) - 1)) {
        $participantData[] = new ConferenceHTML('<br/><hr/><br/>', TRUE);
      }
    }

    return array(
      $this->getNavigation(
        $sessions,
        $session,
        '« ' . iish_t('Go back to sessions list'),
        Url::fromRoute('iish_conference_network_forchairs.network', array(
          'network' => $networkId
        ), array('query' => array('search' => $search))),
        Url::fromRoute('iish_conference_network_forchairs.session', array(
          'network' => $networkId
        ), array('query' => array('search' => $search))),
        'session'
      ),

      array(
        '#theme' => 'iish_conference_container',
        '#styled' => FALSE,
        '#fields' => array_merge($fields, $participantData),
      ),
    );
  }

  /**
   * The network title.
   * @param NetworkApi $network The network.
   * @return string The network title.
   */
  public function getNetworkTitle($network = NULL) {
    try {
      return ($network !== NULL)
        ? $network->getName()
        : iish_t('Sessions: \'@search\'', array('@search' => $this->getSearch()));
    }
    catch (\Exception $exception) {
      return '';
    }
  }

  /**
   * The session title.
   * @param SessionApi $session The session.
   * @return string The session title.
   */
  public function getSessionTitle($session = NULL) {
    try {
      return ($session !== NULL)
        ? $session->getName()
        : iish_t('Individual paper proposals');
    }
    catch (\Exception $exception) {
      return '';
    }
  }

  /**
   * Get the search query.
   * @return null|string The search query.
   */
  private function getSearch() {
    $search = \Drupal::request()->query->get('search', NULL);
    return ($search !== NULL) && (strlen(trim($search)) > 0)
      ? EasyProtection::easyStringProtection($search)
      : NULL;
  }
}
