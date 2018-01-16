<?php
namespace Drupal\iish_conference_preregistration\Form\Page;

use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Form\FormStateInterface;

use Drupal\file\Entity\File;
use Drupal\iish_conference\API\AccessTokenApi;
use Drupal\iish_conference\API\SettingsApi;
use Drupal\iish_conference\API\CRUDApiClient;
use Drupal\iish_conference\API\CachedConferenceApi;

use Drupal\iish_conference\API\Domain\PaperApi;
use Drupal\iish_conference\API\Domain\ParticipantTypeApi;
use Drupal\iish_conference\API\Domain\SessionParticipantApi;

use Drupal\iish_conference\ConferenceMisc;

use Drupal\iish_conference_preregistration\Form\PreRegistrationState;
use Drupal\iish_conference_preregistration\Form\PreRegistrationUtils;

/**
 * The paper page.
 */
class PaperPage extends PreRegistrationPage {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'iish_conference_preregistration_paper';
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
    $state = new PreRegistrationState($form_state);
    $participant = $state->getParticipant();
    $paper = $this->getPaper($state);

    // + + + + + + + + + + + + + + + + + + + + + + + +
    // PAPER

    $form['paper'] = array(
      '#type' => 'fieldset',
      '#title' => iish_t('Register a paper'),
    );

    $form['paper']['papertitle'] = array(
      '#type' => 'textfield',
      '#title' => iish_t('Paper title'),
      '#required' => TRUE,
      '#size' => 40,
      '#maxlength' => 255,
      '#default_value' => $paper->getTitle(),
    );

    if (SettingsApi::getSetting(SettingsApi::REQUIRED_PAPER_UPLOAD, 'bool')) {
      $hasFileUploaded = ($paper->getFileSize() !== NULL && $paper->getFileSize() > 0);

      $maxSize = SettingsApi::getSetting(SettingsApi::MAX_UPLOAD_SIZE_PAPER);
      $allowedExtensions = SettingsApi::getSetting(SettingsApi::ALLOWED_PAPER_EXTENSIONS);

      $description = '';

	  if ($hasFileUploaded) {
	    $description .= '<b>' . iish_t('You have already uploaded a paper "@fileName"<br />Uploading a new file will replace your earlier uploaded paper.', array(
			    "@fileName" => $paper->getFileName()
		    )) . '</b><br />';
	  }

      $description .= iish_t('The file can\'t be larger than <em>@maxSize</em>. ' .
        'Only files with the following extensions are allowed: <em>@extensions</em>.', array(
        '@maxSize' => ConferenceMisc::getReadableFileSize($maxSize),
        '@extensions' => $allowedExtensions
      ));

      $form['paper']['file'] = array(
        '#type' => 'managed_file',
        '#title' => iish_t('Upload paper'),
        '#required' => !$hasFileUploaded,
        '#description' => $description,
        '#upload_validators' => array(
          'file_validate_extensions' => array($allowedExtensions),
          'file_validate_size' => array($maxSize)
        )
      );
    }

    $form['paper']['paperabstract'] = array(
      '#type' => 'textarea',
      '#title' => iish_t('Abstract'),
      '#required' => TRUE,
      '#description' => '<em>' . iish_t('(max. 500 words)') . '</em>',
      '#rows' => 2,
      '#default_value' => $paper->getAbstr(),
    );

    $form['paper']['coauthors'] = array(
      '#type' => 'textfield',
      '#title' => iish_t('Co-authors'),
      '#size' => 40,
      '#maxlength' => 255,
      '#default_value' => $paper->getCoAuthors(),
    );

    if (SettingsApi::getSetting(SettingsApi::SHOW_PAPER_TYPES, 'bool')) {
      $paperTypes = CachedConferenceApi::getPaperTypes();

      if (count($paperTypes) > 0) {
        $form['paper']['type'] = array(
          '#title' => iish_t('Paper type'),
          '#type' => 'select',
          '#options' => CRUDApiClient::getAsKeyValueArray($paperTypes),
          '#default_value' => $paper->getTypeId(),
        );
      }

      if (SettingsApi::getSetting(SettingsApi::SHOW_OPTIONAL_PAPER_TYPE, 'bool')) {
        if (count($paperTypes) > 0) {
          $form['paper']['type']['#empty_option'] = iish_t('Something else');
        }

        $form['paper']['differenttype'] = array(
          '#type' => 'textfield',
          '#size' => 25,
          '#maxlength' => 50,
          '#default_value' => $paper->getDifferentType(),
          '#states' => array(
            'visible' => array(
              'select[name="differenttype"]' => array('value' => ''),
            ),
          ),
        );
      }
      else if (count($paperTypes) > 0) {
        $form['paper']['type']['#required'] = TRUE;
      }
    }

    if (SettingsApi::getSetting(SettingsApi::SHOW_PAPER_KEYWORDS, 'bool')) {
      $form['paper']['keywords'] = array(
        '#type' => 'textarea',
        '#title' => iish_t('Keywords'),
        '#required' => TRUE,
        '#description' => '<em>' . iish_t('Enter keywords seperated with a comma') . '</em>',
        '#rows' => 2,
        '#default_value' => $paper->getKeywords(),
      );
    }

    if (PreRegistrationUtils::useSessions()) {
      $form['paper']['session'] = array(
        '#type' => 'select',
        '#title' => iish_t('Proposed session'),
        '#options' => CachedConferenceApi::getSessionsKeyValue(),
        '#empty_option' => '- ' . iish_t('Select a session') . ' -',
        '#default_value' => $paper->getSessionId(),
        '#attributes' => array('class' => array('iishconference_new_line')),
      );
    }
    else {
      $form['paper']['proposednetwork'] = array(
        '#type' => 'select',
        '#title' => iish_t('Proposed network'),
        '#options' => CRUDApiClient::getAsKeyValueArray(CachedConferenceApi::getNetworks()),
        '#size' => 4,
        '#required' => TRUE,
        '#default_value' => $paper->getNetworkProposalId(),
      );

      PreRegistrationUtils::hideAndSetDefaultNetwork($form['paper']['proposednetwork']);

      if (SettingsApi::getSetting(SettingsApi::SHOW_SESSION_PROPOSAL, 'bool')) {
        $form['paper']['partofexistingsession'] = array(
          '#type' => 'checkbox',
          '#title' => iish_t('Is this part of an existing session?'),
          '#default_value' => (
            ($paper->getSessionProposal() !== NULL) &&
            (strlen(trim($paper->getSessionProposal())) > 0)
          ),
        );

        $form['paper']['proposedsession'] = array(
          '#type' => 'textfield',
          '#title' => iish_t('Proposed session'),
          '#size' => 40,
          '#maxlength' => 255,
          '#default_value' => $paper->getSessionProposal(),
          '#states' => array(
            'visible' => array(
              ':input[name="partofexistingsession"]' => array('checked' => TRUE),
            ),
          ),
        );
      }
    }

    if ((SettingsApi::getSetting(SettingsApi::SHOW_AWARD, 'bool')) && $participant->getStudent()) {
      $awardLink = Link::fromTextAndUrl(iish_t('more about the award'),
        Url::fromUri('award', array('attributes' => array('target' => '_blank'))));

      $form['paper']['award'] = array(
        '#type' => 'checkbox',
        '#title' => iish_t('Would you like to participate in the "@awardName"?',
            array('@awardName' => SettingsApi::getSetting(SettingsApi::AWARD_NAME))) .
          '&nbsp; <em>(' . $awardLink->toString() . ')</em>',
        '#default_value' => $participant->getAward(),
      );
    }

    // + + + + + + + + + + + + + + + + + + + + + + + +
    // AUDIO VISUAL EQUIPMENT

    if (SettingsApi::getSetting(SettingsApi::SHOW_EQUIPMENT, 'bool')) {
      $equipment = CachedConferenceApi::getEquipment();

      $form['equipment'] = array(
        '#type' => 'fieldset',
        '#title' => iish_t('Audio/visual equipment'),
      );

      if (is_array($equipment) && (count($equipment) > 0)) {
        $equipmentOptions = CRUDApiClient::getAsKeyValueArray($equipment);

        $form['equipment']['audiovisual'] = array(
          '#type' => 'checkboxes',
          '#description' => iish_t('Select the equipment you will need for your presentation.'),
          '#options' => $equipmentOptions,
          '#default_value' => $paper->getEquipmentIds(),
        );
      }

      $form['equipment']['extraaudiovisual'] = array(
        '#type' => 'textarea',
        '#title' => iish_t('Extra audio/visual request'),
        '#description' => iish_t('Every room has a beamer and powerpoint available.'),
        '#rows' => 2,
        '#default_value' => $paper->getEquipmentComment(),
      );
    }

    // + + + + + + + + + + + + + + + + + + + + + + + +

    $this->buildPrevButton($form, 'paper_back', iish_t('Back'));
    $this->buildNextButton($form, 'paper_next', iish_t('Save paper'));

    // We can only remove a paper if it has been persisted
    if ($paper->isUpdate()) {
      $this->buildRemoveButton($form, 'paper_remove', iish_t('Remove paper'),
        iish_t('Are you sure you want to remove this paper?'));
    }

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
    if (SettingsApi::getSetting(SettingsApi::SHOW_SESSION_PROPOSAL, 'bool')) {
      if (!PreRegistrationUtils::useSessions() && $form_state->getValue('partofexistingsession')) {
        if (strlen(trim($form_state->getValue('proposedsession'))) === 0) {
          $form_state->setErrorByName('proposedsession',
            iish_t('Proposed session field is required if you check \'Is part of an existing session?\'.'));
        }
      }
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
    $state = new PreRegistrationState($form_state);
    $user = $state->getUser();
    $participant = $state->getParticipant();
    $paper = $this->getPaper($state);

    // First save the paper
    $paper->setUser($user);
    $paper->setTitle($form_state->getValue('papertitle'));
    $paper->setAbstr($form_state->getValue('paperabstract'));
    $paper->setCoAuthors($form_state->getValue('coauthors'));

    if (SettingsApi::getSetting(SettingsApi::SHOW_PAPER_TYPES, 'bool')) {
      $paper->setType($form_state->getValue('type'));

      if (SettingsApi::getSetting(SettingsApi::SHOW_OPTIONAL_PAPER_TYPE, 'bool')) {
        $differentType = ($form_state->getValue('type') == '')
          ? $form_state->getValue('differenttype') : NULL;
        $paper->setDifferentType($differentType);
      }
    }

    if (SettingsApi::getSetting(SettingsApi::SHOW_PAPER_KEYWORDS, 'bool')) {
      $paper->setKeywords($form_state->getValue('keywords'));
    }

    // Either save a session or save a network proposal
    $firstSessionId = $paper->getSessionId();
    if (PreRegistrationUtils::useSessions()) {
      $paper->setSession($form_state->getValue('session'));
    }
    else {
      $paper->setNetworkProposal($form_state->getValue('proposednetwork'));
      if (SettingsApi::getSetting(SettingsApi::SHOW_SESSION_PROPOSAL, 'bool')) {
        $paper->setSessionProposal($form_state->getValue('proposedsession'));
      }
    }

    // Save equipment
    if (SettingsApi::getSetting(SettingsApi::SHOW_EQUIPMENT, 'bool')) {
      $allEquipment = CachedConferenceApi::getEquipment();
      if (is_array($allEquipment) && (count($allEquipment) > 0)) {
        $equipment = array();
        foreach ($allEquipment as $equipmentInstance) {
          $value = $form_state->getValue('audiovisual')[$equipmentInstance->getId()];
          if ($equipmentInstance->getId() == $value) {
            $equipment[] = $equipmentInstance->getId();
          }
        }
        $paper->setEquipment($equipment);
      }

      $paper->setEquipmentComment($form_state->getValue('extraaudiovisual'));
    }

    $paper->save();

    // Then save the participant
    if (SettingsApi::getSetting(SettingsApi::SHOW_AWARD, 'bool') && $participant->getStudent()) {
      $participant->setAward($form_state->getValue('award'));
      $participant->save();
    }

    // If we can add a paper to a session, then also create a session participant registration
    if (PreRegistrationUtils::useSessions()) {
      // We changed the session, remove session registration from the first registration
      if (($paper->getSessionId() !== NULL) &&
        ($firstSessionId !== NULL) &&
        ($paper->getSessionId() != $firstSessionId)
      ) {
        $prevSessionParticipant = PreRegistrationUtils::getSessionParticipantsOfUserWithSessionAndType(
          $state, $firstSessionId, ParticipantTypeApi::AUTHOR_ID
        );

        $prevSessionParticipant->delete();
      }

      $sessionParticipant = PreRegistrationUtils::getSessionParticipantsOfUserWithSessionAndType(
        $state, $paper->getSessionId(), ParticipantTypeApi::AUTHOR_ID
      );

      // We added a session, but have no session participant yet
      if (($paper->getSessionId() !== NULL) && ($sessionParticipant === NULL)) {
        $sessionParticipant = new SessionParticipantApi();
        $sessionParticipant->setUser($user);
        $sessionParticipant->setSession($paper->getSessionId());
        $sessionParticipant->setType(ParticipantTypeApi::AUTHOR_ID);
        $sessionParticipant->save();
      }

      // Or maybe we removed the session, but still have the session participant
      if (($paper->getSessionId() === NULL) && ($sessionParticipant !== NULL)) {
        $sessionParticipant->delete();
      }
    }

    // Then save the paper
    if (SettingsApi::getSetting(SettingsApi::REQUIRED_PAPER_UPLOAD, 'bool')) {
      if (($file = File::load($form_state->getValue(['file', 0]))) !== NULL) {
        $accessTokenApi = new AccessTokenApi();
        $token = $accessTokenApi->accessToken($user->getId());

        $config = \Drupal::config('iish_conference.settings');
        $url = $config->get('conference_base_url') . $config->get('conference_event_code') . '/' .
          $config->get('conference_date_code') . '/' . 'userApi/uploadPaper?access_token=' . $token;

        try {
          $clientFactory = \Drupal::service('http_client_factory');
          $client = $clientFactory->fromOptions(['verify' => FALSE]);

          $response = $client->post($url, array(
            'multipart' => array(
              array(
                'name' => 'paper-id',
                'contents' => $paper->getId()
              ),
              array(
                'name' => 'paper-file',
                'contents' => fopen($file->getFileUri(), 'r'),
                'filename' => $file->getFilename()
              )
            )
          ));

          if ($response->getStatusCode() !== 200) {
            drupal_set_message(iish_t('Failed to upload the paper!'), 'error');
          }
        }
        catch (\Exception $exception) {
          drupal_set_message(iish_t('Failed to upload the paper!'), 'error');
        }
      }
    }

    // Move back to the 'type of registration' page, clean cached data
    $state->setMultiPageData(array());

    $this->nextPageName = PreRegistrationPage::TYPE_OF_REGISTRATION;
  }

  /**
   * Form back button submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function backForm(array &$form, FormStateInterface $form_state) {
    $state = new PreRegistrationState($form_state);
    $state->setMultiPageData(array());

    $this->nextPageName = PreRegistrationPage::TYPE_OF_REGISTRATION;
  }

  /**
   * Form delete button submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function deleteForm(array &$form, FormStateInterface $form_state) {
    $state = new PreRegistrationState($form_state);
    $paper = $this->getPaper($state);
    $paper->delete();

    // If we added the removed paper to a session, then we should also remove the session participant registration
    if (PreRegistrationUtils::useSessions() && ($paper->getSessionId() !== NULL)) {
      $sessionParticipant = PreRegistrationUtils::getSessionParticipantsOfUserWithSessionAndType(
        $state, $paper->getSessionId(), ParticipantTypeApi::AUTHOR_ID
      );

      if ($sessionParticipant !== NULL) {
        $sessionParticipant->delete();
      }
    }

    $state->setMultiPageData(array());

    $this->nextPageName = PreRegistrationPage::TYPE_OF_REGISTRATION;
  }

  /**
   * Returns the stored paper from the pre registration state.
   *
   * @param PreRegistrationState $state The pre registration state.
   *
   * @return PaperApi The paper.
   */
  private static function getPaper($state) {
    $data = $state->getMultiPageData();
    return $data['paper'];
  }
}
