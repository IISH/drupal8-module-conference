<?php
namespace Drupal\iish_conference\ParamConverter;

use Drupal\iish_conference\API\CRUDApiMisc;
use Drupal\iish_conference\API\CachedConferenceApi;

use Drupal\iish_conference\API\Domain\PaperApi;
use Drupal\iish_conference\API\Domain\SessionApi;
use Drupal\iish_conference\API\Domain\NetworkApi;
use Drupal\iish_conference\API\Domain\SentEmailApi;
use Drupal\iish_conference\API\Domain\EventDateApi;

use Drupal\iish_conference\API\Domain\UserApi;
use Symfony\Component\Routing\Route;
use Drupal\iish_conference\EasyProtection;
use Drupal\Core\ParamConverter\ParamConverterInterface;

/**
 * Conference parameter conversion.
 */
class ConferenceParamConverter implements ParamConverterInterface {

  /**
   * Converts path variables to their corresponding objects.
   *
   * @param mixed $value
   *   The raw value.
   * @param mixed $definition
   *   The parameter definition provided in the route options.
   * @param string $name
   *   The name of the parameter.
   * @param array $defaults
   *   The route defaults array.
   *
   * @return mixed|null
   *   The converted parameter value.
   */
  public function convert($value, $definition, $name, array $defaults) {
    switch ($name) {
      case 'network':
        return $this->loadNetwork($value);
      case 'session':
        return $this->loadSession($value);
      case 'sent_email':
        return $this->loadSentEmail($value);
      case 'paper':
        return $this->loadPaper($value);
      case 'event_date':
        return $this->loadEventDate($value);
      case 'user':
        return $this->loadUser($value);
      case 'year':
        return $this->loadEventDate($value);
      default:
        return NULL;
    }
  }

  /**
   * Determines if the converter applies to a specific route and variable.
   *
   * @param mixed $definition
   *   The parameter definition provided in the route options.
   * @param string $name
   *   The name of the parameter.
   * @param \Symfony\Component\Routing\Route $route
   *   The route to consider attaching to.
   *
   * @return bool
   *   TRUE if the converter applies to the passed route and parameter, FALSE
   *   otherwise.
   */
  public function applies($definition, $name, Route $route) {
    if (!empty($definition['type']) && $definition['type'] == 'iish_conference_param_converter') {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Fetches a network based on a network id
   *
   * @param mixed $id The network id
   *
   * @return NetworkApi|null The network in question, or null if not found
   */
  private function loadNetwork($id) {
    $id = EasyProtection::easyIntegerProtection($id);
    $networks = CachedConferenceApi::getNetworks();

    foreach ($networks as $network) {
      if ($network->getId() == $id) {
        return $network;
      }
    }

    return NULL;
  }

  /**
   * Fetches a session based on a session id
   *
   * @param mixed $id The session id
   *
   * @return SessionApi|null The session in question, or null if not found
   */
  private function loadSession($id) {
    return CRUDApiMisc::getById(new SessionApi(), EasyProtection::easyIntegerProtection($id));
  }

  /**
   * Fetches an email based on an email id
   *
   * @param mixed $id The email id
   *
   * @return SentEmailApi|null The email in question, or null if not found
   */
  private function loadSentEmail($id) {
    return CRUDApiMisc::getById(new SentEmailApi(), EasyProtection::easyIntegerProtection($id));
  }

  /**
   * Fetches a paper based on a paper id
   *
   * @param mixed $id The paper id
   *
   * @return PaperApi|null The paper in question, or null if not found
   */
  private function loadPaper($id) {
    return CRUDApiMisc::getById(new PaperApi(), EasyProtection::easyIntegerProtection($id));
  }

  /**
   * Fetches a event date based on the date code
   *
   * @param mixed $yearCode The year code
   *
   * @return EventDateApi|null The event date in question, or null if not found
   */
  private function loadEventDate($yearCode) {
    $eventDates = CachedConferenceApi::getEventDates();

    foreach ($eventDates as $eventDate) {
      if (strtolower($eventDate->getYearCode()) == strtolower($yearCode)) {
        return $eventDate;
      }
    }

    return NULL;
  }

  /**
   * Fetches a user based on a user id
   *
   * @param mixed $id The user id
   *
   * @return UserApi|null The user in question, or null if not found
   */
  private function loadUser($id) {
    return CRUDApiMisc::getById(new UserApi(), EasyProtection::easyIntegerProtection($id));
  }
}
