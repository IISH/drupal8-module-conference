<?php
namespace Drupal\iish_conference_network_individualpapers_xls\Controller;

use Drupal\Core\Controller\ControllerBase;

use Drupal\iish_conference\EasyProtection;
use Drupal\iish_conference\ConferenceTrait;
use Drupal\iish_conference\API\Domain\NetworkApi;

use Symfony\Component\HttpFoundation\Response;

use Drupal\iish_conference_network_individualpapers_xls\API\ParticipantsInNetworkIndividualPaperApi;

/**
 * The controller for the individual papers.
 */
class IndividualPapersController extends ControllerBase {
  use ConferenceTrait;

  /**
   * List all networks.
   * @return Response|array Render array.
   */
  public function listNetworks() {
    if ($this->checkNetworkChair()) return array();

    $networks = $this->getAllowedNetworks();
    if (count($networks) > 0) {
      return array(
        $this->backToPersonalPageLink('nclinks'),

        $this->getLinks(
          iish_t('Networks'), 'networkindividualpapersxls',
          $networks, '(xls)',
          'iish_conference_network_individualpapers_xls.network', 'network'
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
   * @return Response|array The response.
   */
  public function network($network) {
    if ($this->checkNetworkChair()) return array();

    if (!empty($network)) {
      $networkName = EasyProtection::easyAlphaNumericStringProtection($network->getName());
      $participantsApi = new ParticipantsInNetworkIndividualPaperApi();

      if ($participants = $participantsApi->getParticipantsForNetwork($network, TRUE)) {
        return $this->getExcelResponse(
          $participants,
          iish_t('Participants in network @name on @date (individual paper proposals)',
            array('@name' => $networkName, '@date' => date('Y-m-d'))) . '.xls'
        );
      }
    }

    drupal_set_message(iish_t('Failed to create an excel file for download.'), 'error');
    return $this->redirect('iish_conference_network_individualpapers_xls.index');
  }
}
