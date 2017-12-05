<?php
namespace Drupal\iish_conference_preregistration\Form\Page;

use Drupal\Core\Form\FormStateInterface;
use Drupal\iish_conference\API\SettingsApi;
use Drupal\iish_conference_preregistration\Form\PreRegistrationState;

/**
 * The comments page.
 */
class CommentsPage extends PreRegistrationPage {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'iish_conference_preregistration_comments';
  }

  /**
   * Indicates whether this page is open
   *
   * @return bool Returns true if this page is open
   */
  public function isOpen() {
    return SettingsApi::getSetting(SettingsApi::SHOW_GENERAL_COMMENTS, 'bool');
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

    $form['comments'] = array(
      '#type' => 'fieldset',
      '#title' => iish_t('General comments'),
    );

    $form['comments']['comment'] = array(
      '#type' => 'textarea',
      '#title' => '',
      '#rows' => 10,
      '#default_value' => $participant->getExtraInfo(),
    );

    $this->buildPrevButton($form, 'comments_back');
    $this->buildNextButton($form, 'comments_next', iish_t('Next to confirmation page'));

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
    $participant = $state->getParticipant();

    $participant->setExtraInfo($form_state->getValue('comment'));
    $participant->save();

    $this->nextPageName = PreRegistrationPage::CONFIRM;
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
    // Move to the 'type of registration' page if either author or organizer registration had been / is possible
    $showAuthor = SettingsApi::getSetting(SettingsApi::SHOW_AUTHOR_REGISTRATION, 'bool');
    $showOrganizer = SettingsApi::getSetting(SettingsApi::SHOW_ORGANIZER_REGISTRATION, 'bool');
    $typesToShow = SettingsApi::getSetting(SettingsApi::SHOW_SESSION_PARTICIPANT_TYPES_REGISTRATION, 'list');

    if ($showAuthor || $showOrganizer || (count($typesToShow) > 0)) {
      $this->nextPageName = PreRegistrationPage::TYPE_OF_REGISTRATION;
    }
    else {
      $this->nextPageName = PreRegistrationPage::PERSONAL_INFO;
    }
  }
}
