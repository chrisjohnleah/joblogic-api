<?php

declare(strict_types=1);

namespace ChrisJohnLeah\Joblogic\Requests;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Traits\Body\HasJsonBody;
use Saloon\Traits\Plugins\AcceptsJson;

final class SearchRequest extends JoblogicRequest implements HasBody
{
    use AcceptsJson;
    use HasJsonBody;

    protected Method $method = Method::POST;

    /**
     * @param  array<string, mixed>  $body
     */
    public function __construct(
        private readonly string $path,
        private readonly array $payload,
    ) {}

    public function resolveEndpoint(): string
    {
        return ltrim($this->path, '/');
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultBody(): array
    {
        return $this->payload;
    }
}
