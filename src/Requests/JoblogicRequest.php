<?php

declare(strict_types=1);

namespace ChrisJohnLeah\Joblogic\Requests;

use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
use Saloon\Http\Request;

abstract class JoblogicRequest extends Request
{
    public ?int $tries = 3;

    public ?int $retryInterval = 1;

    public ?bool $throwOnMaxTries = false;

    public ?bool $useExponentialBackoff = true;

    public function handleRetry(FatalRequestException|RequestException $exception, Request $request): bool
    {
        $status = $exception instanceof RequestException ? $exception->getResponse()?->status() : null;

        return $status === 429 || ($status !== null && $status >= 500);
    }
}
