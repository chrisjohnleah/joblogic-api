<?php

declare(strict_types=1);

namespace ChrisJohnLeah\Joblogic;

use Saloon\Http\Connector;

/**
 * A deliberately unauthenticated connector for Joblogic's provider-issued
 * upload URI. The URI is returned by Joblogic's API and is only used for the
 * subsequent blob upload; API bearer credentials must never be sent to it.
 */
final class JoblogicUploadConnector extends Connector
{
    public function resolveBaseUrl(): string
    {
        return '';
    }
}
