<?php
namespace Drupal\iish_conference\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * The conference configuration form.
 */
class ConfigForm extends ConfigFormBase {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'iish_conference_config_form';
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
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('iish_conference.settings');

    $form['api_settings'] = array(
      '#type' => 'fieldset',
      '#title' => t('Conference Management System API'),
    );

    $form['api_settings']['conference_client_id'] = array(
      '#type' => 'textfield',
      '#title' => t('Client ID'),
      '#default_value' => $config->get('conference_client_id'),
      '#description' => t('Enter your client ID for communicating with the API. See table oauth_client_details for details.'),
    );

    $form['api_settings']['conference_client_secret'] = array(
      '#type' => 'textfield',
      '#title' => t('Client secret'),
      '#default_value' => $config->get('conference_client_secret'),
      '#description' => t('Enter your client secret for communicating with the API. See table oauth_client_details for details.'),
    );

    $form['api_settings']['conference_base_url'] = array(
      '#type' => 'textfield',
      '#title' => t('Base URL of the CMS'),
      '#default_value' => $config->get('conference_base_url'),
      '#description' => t('Enter the base URL to communicate with. E.g. https://conference.socialhistoryservices.org/'),
    );

    $form['event_date_settings'] = array(
      '#type' => 'fieldset',
      '#title' => t('Conference event and date'),
    );

    $form['event_date_settings']['conference_event_code'] = array(
      '#type' => 'textfield',
      '#title' => t('Event code'),
      '#default_value' => $config->get('conference_event_code'),
      '#description' => t('Enter the code of the current event. E.g. \'esshc\''),
    );

    $form['event_date_settings']['conference_date_code'] = array(
      '#type' => 'textfield',
      '#title' => t('Event date code'),
      '#default_value' => $config->get('conference_date_code'),
      '#description' => t('Enter the code of the current event date. E.g. \'2014\''),
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
    $this->config('iish_conference.settings')
      ->set('conference_client_id', $form_state->getValue('conference_client_id'))
      ->set('conference_client_secret', $form_state->getValue('conference_client_secret'))
      ->set('conference_base_url', $form_state->getValue('conference_base_url'))
      ->set('conference_event_code', $form_state->getValue('conference_event_code'))
      ->set('conference_date_code', $form_state->getValue('conference_date_code'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Gets the configuration names that will be editable.
   *
   * @return array
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   */
  protected function getEditableConfigNames() {
    return ['iish_conference.settings'];
  }
}