<?php
namespace Drupal\iish_conference_changeuser\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use Drupal\iish_conference\API\UserInfoApi;
use Drupal\iish_conference\API\LoggedInUserDetails;
use Drupal\iish_conference\ConferenceTrait;

/**
 * The change user form.
 */
class ChangeUserForm extends FormBase {
  use ConferenceTrait;

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'conference_change_user';
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param string $id
   *   The id or email address of the user to change to.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $id = NULL) {
    if ($this->checkAdmin()) return array();

    $form['hint'] = array(
      '#markup' => '<div class="topmargin">'
        . iish_t('Please enter # or e-mail of user.')
        . '</div>',
    );

    $form['user_id'] = array(
      '#type' => 'textfield',
      '#title' => iish_t('User # or e-mail'),
      '#size' => 30,
      '#maxlength' => 100,
      '#required' => TRUE,
      '#prefix' => '<div class="iishconference_container_inline">',
      '#suffix' => '</div>',
      '#default_value' => $id
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => 'Change'
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
    $userInfoApi = new UserInfoApi();
    $userInfo = $userInfoApi->userInfo(trim($form_state->getValue('user_id')));

    if ($userInfo) {
      if ($userInfo['hasFullRights']) {
        $form_state->setErrorByName('user_id', iish_t('You cannot change into an administrator.'));
        $form_state->setRebuild();
      }
      else {
        $userStatus = LoggedInUserDetails::setCurrentlyLoggedInWithResponse($userInfo);

        if ($userStatus == LoggedInUserDetails::USER_STATUS_EXISTS) {
          drupal_set_message(iish_t('User changed.'));
          $form_state->setRedirect('iish_conference_personalpage.index');
        }
        else {
          switch ($userStatus) {
            case LoggedInUserDetails::USER_STATUS_DISABLED:
              drupal_set_message(iish_t('Account is disabled.'), 'error');
              break;
            case LoggedInUserDetails::USER_STATUS_DELETED:
              drupal_set_message(iish_t('Account is deleted.'), 'error');
              break;
            default:
              drupal_set_message(iish_t('Incorrect email / id.'), 'error');
          }

          $form_state->setRebuild();
        }
      }
    }
    else {
      $form_state->setErrorByName('user_id', iish_t('Cannot find user...'));
      $form_state->setRebuild();
    }
  }
}
