<?php
namespace Drupal\iish_conference_preregistration\Form\Page;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

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

  /**
   * Builds a submit button that triggers a next page action.
   * @param array $form An associative array containing the structure of the form.
   * @param string $name The name of the submit button.
   * @param string|TranslatableMarkup $value The value of the submit button.
   */
  protected function buildNextButton(array &$form, $name, $value = NULL) {
    $this->buildSubmitButton($form, $name, $value ?: iish_t('Next'));
  }

  /**
   * Builds a submit button that triggers a previous page action.
   * @param array $form An associative array containing the structure of the form.
   * @param string $name The name of the submit button.
   * @param string|TranslatableMarkup $value The value of the submit button.
   */
  protected function buildPrevButton(array &$form, $name, $value = NULL) {
    $this->buildSubmitButton($form, $name, $value ?: iish_t('Back to previous step'));
    $form[$name]['#limit_validation_errors'] = array();
    $form[$name]['#nav'] = 'back';
  }

  /**
   * Builds a submit button that triggers a removal action.
   * @param array $form An associative array containing the structure of the form.
   * @param string $name The name of the submit button.
   * @param string|TranslatableMarkup $value The value of the submit button.
   * @param string|TranslatableMarkup $confirm The text of the confirm dialog to show when the button is clicked.
   */
  protected function buildRemoveButton(array &$form, $name, $value = NULL, $confirm = NULL) {
    $this->buildSubmitButton($form, $name, $value ?: iish_t('Remove'));
    $form[$name]['#limit_validation_errors'] = array();
    $form[$name]['#nav'] = 'remove';

    if ($confirm) {
      $form[$name]['#attributes'] = array(
        'onclick' => 'if (!confirm("' . $confirm .  '")) { return false; }'
      );
    }
  }

  /**
   * Builds a simple submit button.
   * @param array $form An associative array containing the structure of the form.
   * @param string $name The name of the submit button.
   * @param string|TranslatableMarkup $value The value of the submit button.
   */
  private function buildSubmitButton(array &$form, $name, $value) {
    $form[$name] = array(
      '#type' => 'submit',
      '#name' => $name,
      '#value' => $value,
      '#submit' => array()
    );
  }
}
