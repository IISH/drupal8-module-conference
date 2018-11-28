<?php
namespace Drupal\iish_conference_preregistration\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\iish_conference\API\LoggedInUserDetails;
use Drupal\iish_conference\API\Domain\UserApi;
use Drupal\iish_conference\API\Domain\ParticipantDateApi;
use Drupal\iish_conference_preregistration\Form\Page\PreRegistrationPage;

/**
 * Handles the state during the pre-registration process.
 */
class PreRegistrationState {
  /**
   * @var \Drupal\Core\Form\FormStateInterface
   */
  private $formState;

  /**
   * Creates the pre registration state for the given form state.
   * @param FormStateInterface $formState The form state.
   */
  public function __construct(FormStateInterface $formState) {
    $this->formState = $formState;
  }

  /**
   * The next page/page the user should go to
   *
   * @param string $page The name of the next page to call
   */
  public function setNextPageName($page) {
    $this->formState->set('pre_registration_page', $page);
    $this->formState->set('pre_registration_data', NULL);
  }

  /**
   * Returns the next page to call.
   *
   * @return PreRegistrationPage The page.
   */
  public function getCurrentPage() {
    if ($this->formState->get('pre_registration_page') === NULL) {
      if (LoggedInUserDetails::isLoggedIn()) {
        $this->formState->set('pre_registration_page', PreRegistrationPage::PERSONAL_INFO);
      }
      else {
        $this->formState->set('pre_registration_page', PreRegistrationPage::LOGIN);
      }
    }

    $page = $this->formState->get('pre_registration_page');
    return new $page();
  }

  /**
   * Caches data only for a single page/page
   *
   * @param array $data The data to cache
   */
  public function setFormData(array $data) {
    $this->formState->set('pre_registration_data', serialize($data));
  }

  /**
   * Caches data for multiple pages/pages
   *
   * @param array $data The data to cache
   */
  public function setMultiPageData(array $data) {
    $this->formState->set('pre_registration_multi_page_data', serialize($data));
  }

  /**
   * Returns the cached data only for a single page/page
   *
   * @return array The cached data
   */
  public function getFormData() {
    if ($this->formState->get('pre_registration_data') === NULL) {
      return array();
    }

    return unserialize($this->formState->get('pre_registration_data'));
  }

  /**
   * Returns the cached data for multiple pages/pages
   *
   * @return array The cached data
   */
  public function getMultiPageData() {
    if ($this->formState->get('pre_registration_multi_page_data') === NULL) {
      return array();
    }

    return unserialize($this->formState->get('pre_registration_multi_page_data'));
  }

  /**
   * Sets the email of the user doing the pre-registration
   *
   * @param string $email The email address
   */
  public function setEmail($email) {
    $this->formState->set('pre_registration_user', NULL);
    $this->formState->set('pre_registration_participant', NULL);

    $this->formState->set('pre_registration_email', strtolower(trim($email)));
  }

  /**
   * Returns the email of the user doing the pre-registration
   *
   * @return string|null The email address or null if not found
   */
  public function getEmail() {
    return $this->formState->get('pre_registration_email');
  }

  /**
   * Returns the user instance doing the pre-registration
   *
   * @return UserApi The user instance
   */
  public function getUser() {
    if (LoggedInUserDetails::isLoggedIn() && (LoggedInUserDetails::getUser() !== NULL)) {
      return LoggedInUserDetails::getUser();
    }
    else {
      $user = new UserApi();
      $user->setEmail($this->getEmail());

      return $user;
    }
  }

  /**
   * Returns the participant instance doing the pre-registration
   *
   * @return ParticipantDateApi The participant instance
   */
  public function getParticipant() {
    if (LoggedInUserDetails::isLoggedIn() && (LoggedInUserDetails::getParticipant() !== NULL)) {
      return LoggedInUserDetails::getParticipant();
    }
    else {
      return new ParticipantDateApi();
    }
  }
}
