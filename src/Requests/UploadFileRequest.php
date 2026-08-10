<?php

declare(strict_types=1);

namespace ChrisJohnLeah\Joblogic\Requests;

use Psr\Http\Message\StreamInterface;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Traits\Body\HasStreamBody;
use Saloon\Traits\RequestProperties\HasHeaders;

final class UploadFileRequest extends JoblogicRequest implements HasBody
{
    use HasHeaders;
    use HasStreamBody;

    protected Method $method = Method::PUT;

    /**
     * @param  resource|StreamInterface  $body
     */
    public function __construct(
        private readonly string $uploadUri,
        private readonly mixed $streamBody,
        private readonly string $contentType,
    ) {}

    public function resolveEndpoint(): string
    {
        return $this->uploadUri;
    }

    protected function defaultHeaders(): array
    {
        return [
            'x-ms-blob-type' => 'BlockBlob',
            'Content-Type' => $this->contentType,
        ];
    }

    /**
     * @return resource|StreamInterface
     */
    protected function defaultBody(): mixed
    {
        return $this->streamBody;
    }
}
