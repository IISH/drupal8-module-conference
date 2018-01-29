<?php
namespace Drupal\iish_conference_network_participants_xls\Controller;

use Drupal\Core\Controller\ControllerBase;

use Drupal\iish_conference\EasyProtection;
use Drupal\iish_conference\ConferenceTrait;
use Drupal\iish_conference\API\Domain\NetworkApi;

use Symfony\Component\HttpFoundation\Response;

use Drupal\iish_conference_network_participants_xls\API\ParticipantsInNetworkApi;

/**
 * The controller for the individual papers.
 */
class ParticipantsController extends ControllerBase {
  use ConferenceTrait;

  /**
   * List all networks.
   * @return array|string|Response Render array.
   */
  public function listNetworks() {
    if ($this->checkNetworkChair()) return array();

    $networks = $this->getAllowedNetworks();
    if (count($networks) > 0) {
      return array(
        $this->backToPersonalPageLink('nclinks'),

        $this->getLinks(
          iish_t('Networks'), 'networkparticipants',
          $networks, ' (xls)',
          'iish_conference_network_participants_xls.network', 'network'
        ),
      );
    }
    else {
      drupal_set_message(iish_t('No networks found!'), 'warning');
      return array();
    }
  }

  /**
   * Download the XLS for the given network.
   * @param NetworkApi $network The network.
   * @return Response|string|array The response.
   */
  public function network($network) {
    if ($this->checkNetworkChair()) return array();

    if (!empty($network)) {
      $networkName = EasyProtection::easyAlphaNumericStringProtection($network->getName());
      $participantsInNetworkApi = new ParticipantsInNetworkApi();

      if ($participants = $participantsInNetworkApi->getParticipantsForNetwork($network, TRUE)) {
        return $this->getExcelResponse(
          $participants,
          iish_t('Participant names and email addresses in network @name on @date',
            array('@name' => $networkName, '@date' => date('Y-m-d'))) . '.xls'
        );
      }
    }

    drupal_set_message(iish_t('Failed to create an excel file for download.'), 'error');
    return $this->redirect('iish_conference_network_participants_xls.index');
  }
}
