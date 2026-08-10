<?php

declare(strict_types=1);

namespace ChrisJohnLeah\Joblogic;

use ChrisJohnLeah\Joblogic\Data\JoblogicCredentials;
use Saloon\Helpers\OAuth2\OAuthConfig;
use Saloon\Http\Connector;
use Saloon\Traits\OAuth2\ClientCredentialsGrant;

final class JoblogicTokenConnector extends Connector
{
    use ClientCredentialsGrant;

    public function __construct(public readonly JoblogicCredentials $credentials) {}

    public function resolveBaseUrl(): string
    {
        return $this->credentials->identityBaseUrl;
    }

    protected function defaultOauthConfig(): OAuthConfig
    {
        return OAuthConfig::make()
            ->setClientId($this->credentials->clientId)
            ->setClientSecret($this->credentials->clientSecret)
            ->setTokenEndpoint('connect/token')
            ->setDefaultScopes([$this->credentials->scope]);
    }
}
