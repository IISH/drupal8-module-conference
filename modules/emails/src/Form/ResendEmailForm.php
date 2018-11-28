<?php
namespace Drupal\iish_conference_emails\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\iish_conference\API\Domain\SentEmailApi;
use Drupal\iish_conference_emails\API\ResendEmailApi;

/**
 * The resend email form.
 */
class ResendEmailForm extends FormBase {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'conference_resend_email';
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
    $form['resend'] = array(
      '#type' => 'submit',
      '#name' => 'resend',
      '#value' => iish_t('(Re)send email now'),
    );

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
    $email = $this->getEmail($form_state);
    $resendEmailApi = new ResendEmailApi();
    if ($resendEmailApi->resendEmail($email)) {
      drupal_set_message(iish_t('Your request for this email has been received and the email has just been sent to you. ' .
        'It can take a while before you will actually receive the email.'), 'status');
    }
  }

  /**
   * Returns the email to be sent once again.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return SentEmailApi The email.
   */
  private function getEmail(FormStateInterface $form_state) {
    return $form_state->get('email');
  }
}