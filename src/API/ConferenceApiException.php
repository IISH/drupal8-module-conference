<?php
namespace Drupal\iish_conference\API;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Thrown on failure to communicate with the Conference API.
 */
class ConferenceApiException extends HttpException {

  /**
   * Construct the exception. Note: The message is NOT binary safe.
   * @param string $message The Exception message to throw.
   * @param \Exception $previous The previous exception used for the exception chaining.
   */
  public function __construct($message, \Exception $previous) {
    parent::__construct(503, $message, $previous);
  }
}
