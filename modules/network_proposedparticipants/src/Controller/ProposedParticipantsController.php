<?php
namespace Drupal\iish_conference_network_proposedparticipants\Controller;

use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Controller\ControllerBase;

use Drupal\iish_conference\ConferenceMisc;
use Drupal\iish_conference\ConferenceTrait;

use Drupal\iish_conference\API\SettingsApi;
use Drupal\iish_conference\API\Domain\NetworkApi;
use Drupal\iish_conference\API\CachedConferenceApi;

use Drupal\iish_conference\Markup\ConferenceHTML;

use Drupal\iish_conference_network_proposedparticipants\API\ParticipantsInProposedNetworkApi;

use Symfony\Component\HttpFoundation\Response;

/**
 * The controller for proposed network participants information for network chairs.
 */
class ProposedParticipantsController extends ControllerBase {
  use ConferenceTrait;

  /**
   * List all networks.
   * @return array|Response Render array or a redirect response.
   */
  public function listNetworks() {
    if ($this->checkNetworkChair()) return array();

    $networks = $this->getAllowedNetworks();
    if (count($networks) > 0) {
      return array(
        $this->backToPersonalPageLink('nclinks'),

        $this->getLinks(
          iish_t('Networks'), 'proposednetworkparticipants',
          $networks, '',
          'iish_conference_network_proposedparticipants.network', 'network'
        ),
      );
    }
    else {
      drupal_set_message(iish_t('No networks found!'), 'warning');
      return array();
    }
  }

  /**
   * List the proposed participants for the given network.
   * @param NetworkApi $network The network.
   * @return array|Response The render array or redirect response.
   */
  public function network($network) {
    if ($this->checkNetworkChair()) return array();

    if (!$network) {
      drupal_set_message(iish_t('The network does not exist.'), 'error');
      return $this->redirect('iish_conference_network_forchairs.index');
    }

    $fields = array();
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

    $participantsInProposedNetworkApi = new ParticipantsInProposedNetworkApi();
    $participantsInProposedNetwork = $participantsInProposedNetworkApi->getParticipantsInProposedNetwork($network);

    $participantData = array();
    foreach ($participantsInProposedNetwork as $i => $participant) {
      if ($i === 0) {
        $participantData[] = new ConferenceHTML('<br/><hr/><br/>', TRUE);
      }

      $user = $participant['user'];
      $paper = $participant['paper'];
      $session = $participant['session'];

      $participantData[] = array(
        'label' => 'Participant name',
        'value' => Link::fromTextAndUrl(
          $user->getFullName(),
          Url::fromUri('mailto:' . $user->getEmail(), array('absolute' => TRUE))
        )->toString(),
        'html' => TRUE,
      );

      if (($user->getOrganisation() !== NULL) && (strlen($user->getOrganisation()) > 0)) {
        $participantData[] = array(
          'label' => 'Organisation',
          'value' => $user->getOrganisation(),
        );
      }

      $participantData[] = array(
        'label' => 'Paper name',
        'value' => $paper->getTitle(),
      );

      if (($paper->getCoAuthors() !== NULL) && (strlen(trim($paper->getCoAuthors())) > 0)) {
        $participantData[] = array(
          'label' => 'Co-authors',
          'value' => $paper->getCoAuthors(),
        );
      }

      $participantData[] = array(
        'label' => 'Paper state',
        'value' => $paper->getState(),
      );

      $participantData[] = array(
        'label' => 'Session name',
        'value' => ($session !== NULL) ? $session->getName() : '<em>(' . iish_t('No session yet') . ')</em>',
        'html' => TRUE,
      );

      $participantData[] = array(
        'label' => 'Paper abstract',
        'value' => ConferenceMisc::getHTMLForLongText($paper->getAbstr()),
        'html' => TRUE,
        'newLine' => TRUE,
      );

      $participantData[] = new ConferenceHTML('<br>', TRUE);
      if ($i < (count($participantsInProposedNetwork) - 1)) {
        $participantData[] = new ConferenceHTML('<br/><hr/><br/>', TRUE);
      }
    }

    return array(
      $this->getNavigation(
        $this->getAllowedNetworks(),
        $network,
        '« ' . iish_t('Go back to networks list'),
        Url::fromRoute('iish_conference_network_proposedparticipants.index'),
        Url::fromRoute('iish_conference_network_proposedparticipants.network'),
        'network'
      ),

      array(
        '#theme' => 'iish_conference_container',
        '#styled' => FALSE,
        '#fields' => array_merge($fields, $participantData),
      ),
    );
  }
}
