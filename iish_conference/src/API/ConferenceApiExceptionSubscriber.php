<?php
namespace Drupal\iish_conference\API;
use Drupal\Core\EventSubscriber\HttpExceptionSubscriberBase;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

/**
 * Exception subscriber which presents the user with an error message if communication with the Conference API fails.
 */
class ConferenceApiExceptionSubscriber extends HttpExceptionSubscriberBase {

  /**
   * Handles errors for this subscriber.
   *
   * @param GetResponseForExceptionEvent $event The event to process.
   */
  public function onException(GetResponseForExceptionEvent $event) {
    $exception = $event->getException();
    if ($exception instanceof ConferenceApiException) {
      $routeMatch = \Drupal::service('current_route_match');
      $renderer = \Drupal::service('main_content_renderer.html');

      $response = $renderer->renderResponse(array(
        '#markup' => '<div class="eca_warning">' . $exception->getMessage() . '</div>'
      ), $event->getRequest(), $routeMatch);
      $response->setStatusCode($exception->getStatusCode());

      $event->setResponse($response);
    }
  }

  /**
   * Specifies the request formats this subscriber will respond to.
   *
   * @return array
   *   An indexed array of the format machine names that this subscriber will
   *   attempt to process, such as "html" or "json". Returning an empty array
   *   will apply to all formats.
   *
   * @see \Symfony\Component\HttpFoundation\Request
   */
  protected function getHandledFormats() {
    return array('html');
  }

  /**
   * Specifies the priority of all listeners in this class.
   *
   * The default priority is 1, which is very low. To have listeners that have
   * a "first attempt" at handling exceptions return a higher priority.
   *
   * @return int
   *   The event priority of this subscriber.
   */
  protected static function getPriority() {
    return 100;
  }
}
