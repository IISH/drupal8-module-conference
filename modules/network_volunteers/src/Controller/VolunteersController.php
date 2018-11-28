<?php
namespace Drupal\iish_conference_network_volunteers\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\iish_conference\API\ApiCriteriaBuilder;
use Drupal\iish_conference\API\CachedConferenceApi;
use Drupal\iish_conference\API\CRUDApiClient;
use Drupal\iish_conference\API\Domain\ParticipantVolunteeringApi;
use Drupal\iish_conference\API\Domain\VolunteeringApi;
use Drupal\iish_conference\API\SettingsApi;
use Drupal\iish_conference\ConferenceMisc;
use Drupal\iish_conference\ConferenceTrait;
use Drupal\iish_conference\API\Domain\NetworkApi;
use Drupal\iish_conference\Markup\ConferenceHTML;
use Symfony\Component\HttpFoundation\Response;

/**
 * The controller for volunteers information for network chairs.
 */
class VolunteersController extends ControllerBase {
  use ConferenceTrait;

  /**
   * List all networks.
   * @return array|string|Response Render array or a redirect response.
   */
  public function listNetworks() {
    if ($this->checkNetworkChair()) return array();

    $networks = $this->getAllowedNetworks();
    if (count($networks) > 0) {
      return array(
        $this->backToPersonalPageLink('nclinks'),

        $this->getLinks(
          iish_t('Networks'), 'networkvolunteers',
          $networks, '',
          'iish_conference_network_volunteers.network', 'network'
        ),
      );
    }
    else {
      drupal_set_message(iish_t('No networks found!'), 'warning');
      return array();
    }
  }

  /**
   * List the volunteers for the given network.
   * @param NetworkApi $network The network.
   * @return array|string|Response The render array or redirect response.
   */
  public function network($network) {
    if ($this->checkNetworkChair()) return array();

    if (!$network) {
      drupal_set_message(iish_t('The network does not exist.'), 'error');
      return $this->redirect('iish_conference_network_volunteers.index');
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

    $volunteerTypes = array();
    foreach (CachedConferenceApi::getVolunteering() as $i => $volunteering) {
      // Make sure we only show chair/discussant, language coach/volunteering if allowed, or additional volunteering
      $isChairDiscussant = (in_array($volunteering->getId(), array(
          VolunteeringApi::CHAIR,
          VolunteeringApi::DISCUSSANT
        )) !== FALSE);
      $isLanguage = (in_array($volunteering->getId(), array(
          VolunteeringApi::COACH,
          VolunteeringApi::PUPIL
        )) !== FALSE);

      $showChairDiscussant = SettingsApi::getSetting(SettingsApi::SHOW_CHAIR_DISCUSSANT_POOL, 'bool');
      $showLanguage = SettingsApi::getSetting(SettingsApi::SHOW_LANGUAGE_COACH_PUPIL, 'bool');

      if (($isChairDiscussant && $showChairDiscussant) ||
        ($isLanguage && $showLanguage) ||
        (!$isChairDiscussant && !$isLanguage)) {
        $volunteerTypes[] = $volunteering;
      }
    }

    $volunteers = array();
    foreach ($volunteerTypes as $i => $volunteering) {
      if ($i === 0) {
        $participantData[] = new ConferenceHTML('<br/><hr/><br/>', TRUE);
      }

      $volunteers[] = $this->getListOfParticipants($volunteering, $network);

      $participantData[] = new ConferenceHTML('<br>', TRUE);
      if ($i < (count($volunteerTypes) - 1)) {
        $participantData[] = new ConferenceHTML('<br/><hr/><br/>', TRUE);
      }
    }

    return array(
      $this->getNavigation(
        $this->getAllowedNetworks(),
        $network,
        '« ' . iish_t('Go back to networks list'),
        Url::fromRoute('iish_conference_network_volunteers.index'),
        Url::fromRoute('iish_conference_network_volunteers.network'),
        'network'
      ),

      array(
        '#theme' => 'iish_conference_container',
        '#styled' => FALSE,
        '#fields' => array_merge($fields, $volunteers),
      ),
    );
  }

  /**
   * Returns the participant details for a given volunteering type and network.
   *
   * @param VolunteeringApi $volunteering Volunteering type in question.
   * @param NetworkApi $network The network in question.
   *
   * @return array Render array.
   */
  private function getListOfParticipants($volunteering, $network) {
    $props = new ApiCriteriaBuilder();
    $participantVolunteering = ParticipantVolunteeringApi::getListWithCriteria(
      $props
        ->eq('volunteering_id', $volunteering->getId())
        ->eq('network_id', $network->getId())
        ->get()
    )->getResults();

    CRUDApiClient::sort($participantVolunteering);

    $rows = array();
    foreach ($participantVolunteering as $participantVolunteer) {
      $rows[] = array(
        $participantVolunteer->getUser()->getLastName(),
        $participantVolunteer->getUser()->getFirstName(),
        Link::fromTextAndUrl($participantVolunteer->getUser()->getEmail(),
          Url::fromUri('mailto:' . $participantVolunteer->getUser()->getEmail(),
            array('absolute' => TRUE))),
        $participantVolunteer->getUser()->getOrganisation(),
      );
    }

    return array(
      '#theme' => 'iish_conference_container',
      '#fields' => array(
        array(
          'header' => iish_t('@name volunteers',
            array('@name' => $volunteering->getDescription()))
        ),
        array(
          '#type' => 'table',
          "#header" => array(
            iish_t('Last name'),
            iish_t('First name'),
            iish_t('E-mail'),
            iish_t('Organisation'),
          ),
          '#sticky' => TRUE,
          '#empty' => iish_t('No volunteers found!'),
          '#rows' => $rows,
        )
      )
    );
  }
}
