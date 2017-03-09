<?php
namespace Drupal\iish_conference_electionadvisory\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use Drupal\iish_conference\API\SettingsApi;
use Drupal\iish_conference\API\CRUDApiMisc;
use Drupal\iish_conference\API\CRUDApiClient;
use Drupal\iish_conference\API\ApiCriteriaBuilder;
use Drupal\iish_conference\API\LoggedInUserDetails;

use Drupal\iish_conference\API\Domain\NetworkChairApi;
use Drupal\iish_conference\API\Domain\ElectionsAdvisoryBoardApi;

use Drupal\iish_conference\ConferenceTrait;
use Symfony\Component\HttpFoundation\Response;

/**
 * The election advisory form.
 */
class ElectionAdvisoryForm extends FormBase {
  use ConferenceTrait;

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'conference_election_advisory';
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if ($response = $this->checkNetworkChair() === TRUE) {
      return array();
    }

    $props = new ApiCriteriaBuilder();
    $hasVotedCount = NetworkChairApi::getListWithCriteria(
      $props
        ->eq('chair_id', LoggedInUserDetails::getId())
        ->eq('votedAdvisoryBoard', TRUE)
        ->get()
    )->getTotalSize();

    if ($hasVotedCount > 0) {
      drupal_set_message(iish_t('You already voted for the advisory board!'), 'warning');
      return $form;
    }

    $nrChoices = intval(SettingsApi::getSetting(SettingsApi::NUM_CANDIDATE_VOTES_ADVISORY_BOARD));
    $candidates = ElectionsAdvisoryBoardApi::getListWithCriteria(array())->getResults();
    $candidatesKeyValue = CRUDApiClient::getAsKeyValueArray($candidates);

    $form['candidates'] = array(
      '#title' => iish_t('Candidates'),
      '#type' => 'checkboxes',
      '#description' => iish_t('Please vote for @nrChoices persons for the election board.',
        array('@nrChoices' => $nrChoices)),
      '#options' => $candidatesKeyValue,
      '#required' => TRUE,
    );

    $form['submit-votes'] = array(
      '#type' => 'submit',
      '#name' => 'submit-votes',
      '#value' => iish_t('Submit votes'),
    );

    return $form;
  }

  /**
   * Form validation handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $nrChoices = intval(SettingsApi::getSetting(SettingsApi::NUM_CANDIDATE_VOTES_ADVISORY_BOARD));

    $count = 0;
    foreach ($form_state->getValue('candidates') as $candidateId => $candidateValue) {
      $count = ($candidateId == $candidateValue) ? ($count + 1) : $count;
    }

    if ($count !== $nrChoices) {
      $form_state->setErrorByName('candidates', iish_t('Make sure to vote for exactly @nrChoices persons for the election board.',
        array('@nrChoices' => $nrChoices)));
    }
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $candidates = ElectionsAdvisoryBoardApi::getListWithCriteria(array())->getResults();

    // Increase the number of votes for the candidates voted for by the network chair
    foreach ($form_state->getValue('candidates') as $candidateId => $candidateValue) {
      if ($candidateId == $candidateValue) {
        foreach ($candidates as $candidate) {
          if ($candidate->getId() == $candidateId) {
            $candidate->vote();
            $candidate->save();
          }
        }
      }
    }

    // Indicate that the chair has made its vote for the advisory board
    $chairs = CRUDApiMisc::getAllWherePropertyEquals(
      new NetworkChairApi(), 'chair_id', LoggedInUserDetails::getId())->getResults();

    foreach ($chairs as $chair) {
      $chair->setVotedAdvisoryBoard(TRUE);
      $chair->save();
    }

    // Redirect back to the personal page
    drupal_set_message('Thank you for your vote!', 'status');
    $form_state->setRedirect('iish_conference_personalpage.index');
  }
}
