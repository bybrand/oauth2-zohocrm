<?php

namespace Bybrand\OAuth2\Client\Provider;

use Psr\Http\Message\ResponseInterface;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;

class ZohoCRM extends AbstractProvider
{
    use BearerAuthorizationTrait;

    /**
     * @var string If set, this will be sent to zoho as the "access_type" parameter.
     * @link https://www.zoho.com/crm/developer/docs/api/v2/auth-request.html#Parameters
     */
    protected $accessType;

    /**
     * The accounts server host for multi-DC support.
     * @var string
     */
    protected $accountsServer = 'https://accounts.zoho.com';

    /**
     * The host server location for multi-DC support.
     * @var string
     */
    protected $hostResourceLocation = 'https://www.zohoapis.com';

    /**
     * Get authorization url to begin OAuth flow
     *
     * @return string
     */
    public function getBaseAuthorizationUrl()
    {
        return $this->accountsServer . '/oauth/v2/auth';
    }

    /**
     * Get access token url to retrieve token
     *
     * @param array $params
     *
     * @return string
     */
    public function getBaseAccessTokenUrl(array $params)
    {
        return $this->accountsServer . '/oauth/v2/token';
    }

    /**
     * Get provider url to fetch organization details
     *
     * @param AccessToken $token
     *
     * @return string
     */
    public function getResourceOwnerDetailsUrl(AccessToken $token)
    {
        return $this->hostResourceLocation . '/crm/v2/org';
    }

    protected function getAuthorizationParameters(array $options)
    {
        $params = array_merge(
            parent::getAuthorizationParameters($options),
            array_filter([
                'access_type' => $this->accessType,
            ])
        );
        return $params;
    }

    /**
     * Get the default scopes used by this provider.
     *
     * @return array
     */
    protected function getDefaultScopes()
    {
        return [];
    }

    /**
     * Check a provider response for errors.
     *
     * @throws IdentityProviderException
     * @param  ResponseInterface $response
     * @param  string $data Parsed response data
     * @return void
     */
    protected function checkResponse(ResponseInterface $response, $data)
    {
        if ($response->getStatusCode() >= 400) {
            throw new IdentityProviderException(
                $response->getReasonPhrase(),
                $response->getStatusCode(),
                $response
            );
        }
    }

    /**
     * Generate a user object from a successful user details request.
     *
     * @param array $response
     * @param AccessToken $token
     *
     * @return ZohoCRMResourceOwner
     */
    protected function createResourceOwner(array $response, AccessToken $token)
    {
        return new ZohoCRMResourceOwner($response);
    }
}
