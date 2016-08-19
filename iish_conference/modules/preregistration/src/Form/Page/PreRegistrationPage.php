<?php
namespace Drupal\iish_conference_preregistration\Form\Page;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * The base class for all pages of the pre registration process.
 */
abstract class PreRegistrationPage extends FormBase {
  const LOGIN = '\Drupal\iish_conference_preregistration\Form\Page\LoginPage';
  const PASSWORD = '\Drupal\iish_conference_preregistration\Form\Page\PasswordPage';
  const PERSONAL_INFO = '\Drupal\iish_conference_preregistration\Form\Page\PersonalInfoPage';
  const TYPE_OF_REGISTRATION = '\Drupal\iish_conference_preregistration\Form\Page\TypeOfRegistrationPage';
  const PAPER = '\Drupal\iish_conference_preregistration\Form\Page\PaperPage';
  const SESSION = '\Drupal\iish_conference_preregistration\Form\Page\SessionPage';
  const SESSION_PARTICIPANT = '\Drupal\iish_conference_preregistration\Form\Page\SessionParticipantPage';
  const SESSION_PARTICIPANT_TYPES = '\Drupal\iish_conference_preregistration\Form\Page\SessionParticipantTypesPage';
  const COMMENTS = '\Drupal\iish_conference_preregistration\Form\Page\CommentsPage';
  const CONFIRM = '\Drupal\iish_conference_preregistration\Form\Page\ConfirmPage';

  protected $nextPageName = NULL;

  /**
   * Indicates whether this page is open
   *
   * @return bool Returns true if this page is open
   */
  public function isOpen() {
    return TRUE;
  }

  /**
   * Determine the next page name.
   *
   * @return string The name of the next page.
   */
  public function getNextPageName() {
    return $this->nextPageName;
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
    // Back is optional.
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
    // Delete is optional.
  }
}
