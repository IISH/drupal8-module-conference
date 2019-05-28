<?php
namespace Drupal\iish_conference_preregistration\Controller;

use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Controller\ControllerBase;

use Drupal\iish_conference\Markup\ConferenceHTML;
use Drupal\iish_conference\ConferenceMisc;

use Drupal\iish_conference\API\SettingsApi;
use Drupal\iish_conference\API\CachedConferenceApi;

/**
 * The controller for the pre registration.
 */
class PreRegistrationController extends ControllerBase {

  /**
   * The page to show after the user has completed pre registration.
   * @return array Render array.
   */
  public function completed() {
    $fields = array(
      new ConferenceHTML(
        '<div class="eca_remark heavy bottommargin">'
        . iish_t('You are now pre-registered for the @conference conference.',
          array(
            '@conference' => CachedConferenceApi::getEventDate()
              ->getLongNameAndYear()
          ))
        . '<br>' . iish_t('In a few minutes you will receive by e-mail a copy of your pre-registration.')
        . '</div>', TRUE),

      new ConferenceHTML(
        '<div class="eca_remark heavy bottommargin">'
        . iish_t('It is not possible to modify your pre-registration anymore.')
        . '<br />' . iish_t('If you would like to modify your registration please send an email to @email.',
          array('@email' => ConferenceMisc::emailLink(SettingsApi::getSetting(SettingsApi::DEFAULT_ORGANISATION_EMAIL))->toString()))
        . '</div>', TRUE)
    );

//    if ($this->moduleHandler()->moduleExists('iish_conference_personalpage')) {
//      $personalPageLink = Link::fromTextAndUrl(iish_t('personal page'),
//        Url::fromRoute('iish_conference_personalpage.index'));
//
//      $fields[] = new ConferenceHTML(
//        '<div class="eca_remark heavy bottommargin">'
//        . iish_t('Go to your @link.', array('@link' => $personalPageLink->toString()))
//        . '</div>', TRUE);
//    }

    $isFinalRegistrationOpen = SettingsApi::getSetting(SettingsApi::FINAL_REGISTRATION_LASTDATE, 'lastdate');
    if ($isFinalRegistrationOpen && $this->moduleHandler()
        ->moduleExists('iish_conference_finalregistration')
    ) {
      $finalRegistrationLink = Link::fromTextAndUrl(iish_t('final registration and payment'),
        Url::fromRoute('iish_conference_finalregistration.form'));

      $fields[] = new ConferenceHTML(
        '<div class="eca_remark heavy bottommargin">'
        . iish_t('You have just pre-registered. Please go now to @link.',
          array('@link' => $finalRegistrationLink->toString()))
        . '</div>', TRUE);
    }

    return array(
      '#theme' => 'iish_conference_container',
      '#fields' => $fields
    );
  }

  /**
   * The title.
   * @return string The title.
   */
  public function getTitle() {
    try {
      return iish_t('Pre-registration for the') . ' ' . CachedConferenceApi::getEventDate()
        ->getLongNameAndYear();
    }
    catch (\Exception $exception) {
      return t('Pre-registration');
    }
  }
}
