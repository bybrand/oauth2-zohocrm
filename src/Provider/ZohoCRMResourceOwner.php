<?php

namespace Bybrand\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Tool\ArrayAccessorTrait;

class ZohoCRMResourceOwner implements ResourceOwnerInterface
{
    use ArrayAccessorTrait;

    /**
     * Raw response
     *
     * @var array
     */
    protected $response;

    /**
     * Creates new resource owner.
     *
     * @param array  $response
     */
    public function __construct(array $response = [])
    {
        $this->response = $response;
    }

    /**
     * Get resource owner id
     *
     * @return string
     */
    public function getId()
    {
        return $this->getValueByKey($this->response, 'org.0.id');
    }

    /**
     * Get resource owner organization Name
     *
     * @return string|null
     */
    public function getOrganizationName()
    {
        return $this->getValueByKey($this->response, 'org.0.company_name');
    }

    /**
     * Return all of the owner details available as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->response;
    }
}
