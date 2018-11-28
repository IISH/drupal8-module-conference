<?php
namespace Drupal\iish_conference\API;

/**
 * API that allows a user to login automatically using a code
 */
class AutoLoginApi {
	private $client;
	private static $apiName = 'autoLogin';

	public function __construct() {
		$this->client = new ConferenceApiClient();
	}

	/**
	 * Allows a user to login with his id and auto login code
	 *
   * @param int $id The id of the user
	 * @param string $autoLoginCode The auto login code of the user
	 *
	 * @return int The status
	 */
	public function login($id, $autoLoginCode) {
		$response = $this->client->get(self::$apiName, array(
      'id' => $id,
			'code' => trim($autoLoginCode),
		));

		return LoggedInUserDetails::setCurrentlyLoggedInWithResponse($response);
	}
} 