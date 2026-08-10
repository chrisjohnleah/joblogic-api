<?php

declare(strict_types=1);

namespace ChrisJohnLeah\Joblogic\Data;

use InvalidArgumentException;

final readonly class JoblogicCredentials
{
    public const PRODUCTION_API_BASE_URL = 'https://api.joblogic.com/api/v1';

    public const UAT_API_BASE_URL = 'https://uatapi.joblogic.com/api/v1';

    public const PRODUCTION_IDENTITY_BASE_URL = 'https://identityserver.joblogic.com';

    public const UAT_IDENTITY_BASE_URL = 'https://uatidentityserver.joblogic.com';

    public function __construct(
        public string $clientId,
        public string $clientSecret,
        public string $tenantId,
        public string $apiBaseUrl = self::PRODUCTION_API_BASE_URL,
        public string $identityBaseUrl = self::PRODUCTION_IDENTITY_BASE_URL,
        public string $scope = 'JL.Api',
    ) {
        if ($this->clientId === '' || $this->clientSecret === '' || $this->tenantId === '') {
            throw new InvalidArgumentException('Joblogic client ID, client secret, and tenant ID are required.');
        }
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public static function fromArray(array $values, string $environment = 'production'): self
    {
        $uat = $environment === 'uat';

        return new self(
            clientId: (string) ($values['client_id'] ?? $values['clientId'] ?? ''),
            clientSecret: (string) ($values['client_secret'] ?? $values['clientSecret'] ?? ''),
            tenantId: (string) ($values['tenant_id'] ?? $values['tenantId'] ?? ''),
            apiBaseUrl: rtrim((string) ($values['api_base_url'] ?? $values['apiBaseUrl'] ?? ($uat ? self::UAT_API_BASE_URL : self::PRODUCTION_API_BASE_URL)), '/'),
            identityBaseUrl: rtrim((string) ($values['identity_base_url'] ?? $values['identityBaseUrl'] ?? ($uat ? self::UAT_IDENTITY_BASE_URL : self::PRODUCTION_IDENTITY_BASE_URL)), '/'),
            scope: (string) ($values['scope'] ?? 'JL.Api'),
        );
    }

    public static function forEnvironment(
        string $clientId,
        string $clientSecret,
        string $tenantId,
        string $environment = 'production',
    ): self {
        return self::fromArray([
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'tenant_id' => $tenantId,
        ], $environment);
    }
}
