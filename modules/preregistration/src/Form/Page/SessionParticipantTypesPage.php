<?php
namespace Drupal\iish_conference_preregistration\Form\Page;

use Drupal\Core\Form\FormStateInterface;

use Drupal\iish_conference\API\CachedConferenceApi;
use Drupal\iish_conference\API\Domain\SessionParticipantApi;

use Drupal\iish_conference_preregistration\Form\PreRegistrationState;
use Drupal\iish_conference_preregistration\Form\PreRegistrationUtils;

/**
 * The session participant types page.
 */
class SessionParticipantTypesPage extends PreRegistrationPage {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'iish_conference_preregistration_session_participant_types';
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
    $formData = array();

    // + + + + + + + + + + + + + + + + + + + + + + + +

    foreach (PreRegistrationUtils::getParticipantTypesForUser() as $participantType) {
      $form['type_' . $participantType->getId()] = array(
        '#type' => 'fieldset',
        '#title' => iish_t('I would like to be a @type in the sessions',
          array('@type' => strtolower(iish_t($participantType)))),
      );

      $storedSessionTypes = PreRegistrationUtils::getSessionParticipantsOfUserWithType($state, $participantType);
      $formData['sessions_' . $participantType->getId()] = $storedSessionTypes;

      $form['type_' . $participantType->getId()]['session_' . $participantType->getId()] = array(
        '#type' => 'select',
        '#title' => '',
        '#options' => CachedConferenceApi::getSessionsKeyValue(),
        '#size' => 12,
        '#multiple' => TRUE,
        '#default_value' => SessionParticipantApi::getForMethod($storedSessionTypes, 'getSessionId'),
        '#attributes' => array('class' => array('iishconference_new_line')),
        '#description' => '<i>' . iish_t('Use CTRL key to select multiple sessions.') . '</i>',
      );
    }

    $state->setFormData($formData);

    // + + + + + + + + + + + + + + + + + + + + + + + +

    $this->buildPrevButton($form, 'sessionparticipanttypes_back', iish_t('Back'));
    $this->buildNextButton($form, 'sessionparticipanttypes_next', iish_t('Save'));

    return $form;
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
    $data = $state->getFormData();

    $sessions = CachedConferenceApi::getSessionsKeyValue();

    foreach (PreRegistrationUtils::getParticipantTypesForUser() as $participantType) {
      $allToDelete = $data['sessions_' . $participantType->getId()];
      $sessionIdsForType = $form_state->getValue('session_' . $participantType->getId());

      foreach ($sessionIdsForType as $sessionId) {
        $foundInstance = FALSE;

        foreach ($allToDelete as $key => $instance) {
          if ($instance->getSessionId() == $sessionId) {
            $foundInstance = TRUE;
            unset($allToDelete[$key]);
            break;
          }
        }

        if (!$foundInstance && array_key_exists($sessionId, $sessions)) {
          $sessionParticipant = new SessionParticipantApi();
          $sessionParticipant->setUser($user);
          $sessionParticipant->setAddedBy($user);
          $sessionParticipant->setSession($sessionId);
          $sessionParticipant->setType($participantType);
          $sessionParticipant->save();
        }
      }

      // Delete all previously saved session participant choices that were not chosen again
      foreach ($allToDelete as $instance) {
        $instance->delete();
      }
    }

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
}
