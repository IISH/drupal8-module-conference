<?php
namespace Drupal\iish_conference_personalpage\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\iish_conference_personalpage\API\RemovePaperApi;

/**
 * The delete paper form.
 */
class DeletePaperForm extends FormBase {
  
  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'conference_delete_paper';
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
    $form['#attributes'] = array('class' => array('iishconference_inline_no_block'));

    $form['remove-paper'] = array(
      '#type' => 'submit',
      '#name' => 'remove-paper',
      '#value' => iish_t('Remove uploaded paper'),
      '#attributes' => array(
        'onclick' =>
          'if (!confirm("' . iish_t('Are you sure you want to remove the uploaded paper?') . '")) { return false; }'
      ),
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
    $removePaperApi = new RemovePaperApi();
    if ($removePaperApi->removePaper($form_state->get('paper'))) {
      drupal_set_message(iish_t('Your paper has been successfully removed!'), 'status');
    }
  }
}
