<?php

declare(strict_types=1);

namespace ChrisJohnLeah\Joblogic\Requests;

use Saloon\Enums\Method;
use Saloon\Traits\Plugins\AcceptsJson;
use Saloon\Traits\RequestProperties\HasQuery;

final class GetRequest extends JoblogicRequest
{
    use AcceptsJson;
    use HasQuery;

    protected Method $method = Method::GET;

    /**
     * @param  array<string, mixed>  $query
     */
    public function __construct(
        private readonly string $path,
        private readonly array $parameters = [],
    ) {}

    public function resolveEndpoint(): string
    {
        return ltrim($this->path, '/');
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultQuery(): array
    {
        return $this->parameters;
    }
}
