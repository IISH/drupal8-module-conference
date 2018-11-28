<?php
namespace Drupal\iish_conference_personalpage\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use Drupal\iish_conference\API\LoggedInUserDetails;

use Drupal\iish_conference\ConferenceMisc;
use Drupal\iish_conference\ConferenceTrait;

/**
 * The dietary wishes form.
 */
class DietaryWishesForm extends FormBase {
  use ConferenceTrait;

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'conference_dietary_wishes';
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
    // redirect to login page
    if ($this->redirectIfNotLoggedIn()) return [];

    $user = LoggedInUserDetails::getUser();

    $form['dietary-wish'] = [
      '#title' => iish_t('Foodwise, I identify as a'),
      '#type' => 'radios',
      '#required' => TRUE,
      '#options' => ConferenceMisc::getDietaryWishesOptions(),
      '#default_value' => $user->getDietaryWishes(),
    ];

    $form['other-dietary-wish'] = [
      '#type' => 'textfield',
      '#size' => 50,
      '#maxlength' => 255,
      '#default_value' => $user->getOtherDietaryWishes(),
    ];

    $form['submit-dietary-wishes'] = [
      '#type' => 'submit',
      '#name' => 'submit-dietary-wishes',
      '#value' => iish_t('Submit dietary wishes'),
    ];

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
    if (($form_state->getValue('dietary-wish') == 0)
      && (strlen($form_state->getValue('other-dietary-wish')) <= 0)) {
      $form_state->setErrorByName('other-dietary-wish', iish_t('Please specify your dietary wishes'));
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
    $user = LoggedInUserDetails::getUser();
    $user->setDietaryWishes($form_state->getValue('dietary-wish'));
    $user->setOtherDietaryWishes($form_state->getValue('other-dietary-wish'));
    $user->save();

    // Redirect back to the personal page
    drupal_set_message('Your preferences have been saved!', 'status');
    $form_state->setRedirect('iish_conference_personalpage.index');
  }
}
