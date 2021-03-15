<?php

namespace Bybrand\OAuth2\Client\Test;

use PHPUnit\Framework\TestCase;

use Bybrand\OAuth2\Client\Provider\ZohoCRM;
use Mockery as m;

class ZohoCRMTest extends TestCase
{
    protected $provider;

    protected function setUp(): void
    {
        $this->provider = new ZohoCRM([
            'clientId'     => 'mock_client_id',
            'clientSecret' => 'mock_secret',
            'redirectUri'  => 'none',
            // 'accessType'     => 'offline',
            // 'accountsServer'       => 'https://accounts.zoho.com',
            // 'hostResourceLocation' => 'https://desk.zoho.com'
        ]);
    }

    public function tearDown(): void
    {
        m::close();
        parent::tearDown();
    }

    /**
     * @group ZohoCRM.AuthorizationUrl
     */
    public function testAuthorizationUrl()
    {
        $url = $this->provider->getAuthorizationUrl();
        $uri = parse_url($url);
        parse_str($uri['query'], $query);

        $this->assertArrayHasKey('client_id', $query);
        $this->assertArrayHasKey('response_type', $query);
        $this->assertArrayHasKey('client_id', $query);
        $this->assertArrayHasKey('redirect_uri', $query);
        $this->assertArrayHasKey('state', $query);
        $this->assertArrayHasKey('scope', $query);
        $this->assertArrayHasKey('approval_prompt', $query);
        $this->assertNotNull($this->provider->getState());
    }

    /**
     * @group ZohoCRM.GetAuthorizationUrl
     */
    public function testGetAuthorizationUrl()
    {
        $url = $this->provider->getAuthorizationUrl();
        $uri = parse_url($url);

        $this->assertEquals('/oauth/v2/auth', $uri['path']);
    }

    /**
     * @group ZohoCRM.GetBaseAccessTokenUrl
     */
    public function testGetBaseAccessTokenUrl()
    {
        $params = [];

        $url = $this->provider->getBaseAccessTokenUrl($params);
        $uri = parse_url($url);

        $this->assertEquals('/oauth/v2/token', $uri['path']);
    }

    /**
     * @group ZohoCRM.GetAccessToken
     */
    public function testGetAccessToken()
    {
        $jsonReturned = [
            'access_token'   => 'mock_access_token',
            'refresh_token'  => 'mock_refresh_token',
            // 'expires_in_sec' => 3600,
            'token_type'     => 'Bearer',
            'expires_in'     => 3600,
            'access_type'    => 'offline', // To refresh token
            'api_domain'     => 'https://www.zohoapis.com'
        ];

        $response = m::mock('Psr\Http\Message\ResponseInterface');
        $response->shouldReceive('getBody')->andReturn(json_encode($jsonReturned));
        $response->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);
        $response->shouldReceive('getStatusCode')->andReturn(200);

        $client = m::mock('GuzzleHttp\ClientInterface');
        $client->shouldReceive('send')->times(1)->andReturn($response);

        $this->provider->setHttpClient($client);
        $token = $this->provider->getAccessToken('authorization_code', [
            'code' => 'mock_authorization_code'
        ]);

        $this->assertEquals('mock_access_token', $token->getToken());
        $this->assertEquals('mock_refresh_token', $token->getRefreshToken());
        $this->assertLessThanOrEqual(time() + 3600, $token->getExpires());
        $this->assertGreaterThanOrEqual(time(), $token->getExpires());
    }

    /**
     * @group ZohoCRM.GetResourceOwner
     */
    public function testGetResourceOwner()
    {
        $accessTokenJson = [
            'access_token'   => 'mock_access_token',
            'refresh_token'  => 'mock_refresh_token',
            'token_type'     => 'Bearer',
            'expires_in'     => 3600
        ];

        // Set organization Id.
        $organizationId = uniqid();

        $userJson['data'][] = [
            'id'           => $organizationId,
            'company_name' => 'Zylker'
        ];

        $response = m::mock('Psr\Http\Message\ResponseInterface');
        $response->shouldReceive('getBody')->andReturn(json_encode($accessTokenJson));
        $response->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);
        $response->shouldReceive('getStatusCode')->andReturn(200);

        $userResponse = m::mock('Psr\Http\Message\ResponseInterface');
        $userResponse->shouldReceive('getBody')->andReturn(json_encode($userJson));
        $userResponse->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);
        $userResponse->shouldReceive('getStatusCode')->andReturn(200);

        $client = m::mock('GuzzleHttp\ClientInterface');
        $client->shouldReceive('send')->times(2)->andReturn($response, $userResponse);

        $this->provider->setHttpClient($client);
        $token = $this->provider->getAccessToken('authorization_code', [
            'code' => 'mock_authorization_code'
        ]);

        $organization = $this->provider->getResourceOwner($token);

        $this->assertEquals($organizationId, $organization->getId());
        $this->assertEquals('Zylker', $organization->getOrganizationName());
    }
}
