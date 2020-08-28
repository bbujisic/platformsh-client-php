<?php

namespace Platformsh\Client\Connection;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Request;
use Platformsh\Client\Session\SessionInterface;

interface ConnectorInterface
{

    /**
     * Get the session instance for this connection.
     *
     * @return SessionInterface
     */
    public function getSession();

    /**
     * Log in to Platform.sh.
     *
     * @param string $username
     * @param string $password
     * @param bool   $force
     *   Whether to re-authenticate even if the session appears to be logged
     *   in already.
     * @param string|int $totp
     *   Time-based one-time password (two-factor authentication).
     */
    public function logIn($username, $password, $force = false, $totp = null);

    /**
     * Log out.
     */
    public function logOut();

    /**
     * Check whether the user is logged in.
     *
     * @return bool
     */
    public function isLoggedIn();

    /**
     * Get an authenticated Guzzle client.
     *
     * This will fail if the user is not logged in.
     *
     * @return ClientInterface
     */
    public function getClient();

    /**
     * Set the API token to use for Platform.sh requests.
     *
     * @param string $token
     *   The token value.
     * @param string $type
     *   The token type 'exchange' for exchangeable tokens (recommended), or
     *   'access' for direct personal access tokens.
     */
    public function setApiToken($token, $type);

    /**
     * Get the configured accounts endpoint URL.
     *
     * @return string
     */
    public function getAccountsEndpoint();

    /**
     * Create and send a Guzzle request.
     *
     * Using this method allows exceptions to be standardized.
     *
     * @param   string  $resourcePath   Path to the resource.
     * @param   string  $method         HTTP request method
     * @param   array   $options        Guzzle options
     *
     * @return  array
     */
    public function sendToAccounts(string $resourcePath, string $method = 'get',array $options = []);

    /**
     * Send a Guzzle request.
     *
     * Using this method allows exceptions to be standardized.
     *
     * @param   Request $request    Guzzle Request object
     * @param   array   $options    Guzzle options
     *
     * @return  array
     */
    public function sendToUri(string $uri, string $method = 'get', array $options = []);

}
