<?php

declare(strict_types=1);

namespace ChrisJohnLeah\Joblogic;

use ChrisJohnLeah\Joblogic\Data\JoblogicCredentials;
use ChrisJohnLeah\Joblogic\Data\JoblogicPage;
use ChrisJohnLeah\Joblogic\Data\JoblogicResponse;
use ChrisJohnLeah\Joblogic\Requests\CreateNoteRequest;
use ChrisJohnLeah\Joblogic\Requests\GetRequest;
use ChrisJohnLeah\Joblogic\Requests\SearchRequest;
use ChrisJohnLeah\Joblogic\Requests\UploadFileRequest;
use Psr\Http\Message\StreamInterface;
use Saloon\Contracts\Authenticator;
use Saloon\Http\Auth\AccessTokenAuthenticator;
use Saloon\Http\Connector;
use Saloon\Http\Faking\MockClient;

final class JoblogicClient extends Connector
{
    private readonly AccessTokenAuthenticator $accessTokenAuthenticator;

    public function __construct(
        public readonly JoblogicCredentials $credentials,
        string $accessToken,
    ) {
        $this->accessTokenAuthenticator = new AccessTokenAuthenticator($accessToken);
    }

    public static function fromClientCredentials(JoblogicCredentials $credentials, ?MockClient $mockClient = null): self
    {
        $tokenConnector = new JoblogicTokenConnector($credentials);

        if ($mockClient !== null) {
            $tokenConnector->withMockClient($mockClient);
        }

        $authenticator = $tokenConnector->getAccessToken();
        $client = new self($credentials, $authenticator->getAccessToken());

        return $mockClient === null ? $client : $client->withMockClient($mockClient);
    }

    public function resolveBaseUrl(): string
    {
        return $this->credentials->apiBaseUrl;
    }

    protected function defaultAuth(): ?Authenticator
    {
        return $this->accessTokenAuthenticator;
    }

    /**
     * Execute one of Joblogic's POST-based collection searches.
     *
     * @param  array<string, mixed>  $body
     */
    public function search(
        string $path,
        array $body = [],
        int $pageIndex = 1,
        int $pageSize = 50,
    ): JoblogicPage {
        $body = array_replace([
            'TenantId' => $this->credentials->tenantId,
            'PageIndex' => $pageIndex,
            'PageSize' => $pageSize,
        ], $body);

        // Do not allow a caller to escape the tenant or page requested by the
        // client. Reassigning existing keys preserves the stable request
        // shape used by provider fixtures and logs.
        $body['TenantId'] = $this->credentials->tenantId;
        $body['PageIndex'] = $pageIndex;
        $body['PageSize'] = $pageSize;

        return JoblogicPage::fromResponse(
            JoblogicResponse::fromSaloon($this->send(new SearchRequest($path, $body))),
            $pageIndex,
            $pageSize,
        );
    }

    /**
     * Convenience methods for the documented tenant-wide collection searches.
     * They keep provider paths in the SDK while leaving migration policy to
     * the consuming application.
     *
     * @param  array<string, mixed>  $body
     */
    public function customers(array $body = [], int $pageIndex = 1, int $pageSize = 50): JoblogicPage
    {
        return $this->search('Customer/GetAll', $body, $pageIndex, $pageSize);
    }

    /** @param array<string, mixed> $body */
    public function contacts(array $body = [], int $pageIndex = 1, int $pageSize = 50): JoblogicPage
    {
        return $this->search('Contact/GetAll', $body, $pageIndex, $pageSize);
    }

    /** @param array<string, mixed> $body */
    public function sites(array $body = [], int $pageIndex = 1, int $pageSize = 50): JoblogicPage
    {
        return $this->search('Site/GetAll', $body, $pageIndex, $pageSize);
    }

    /** @param array<string, mixed> $body */
    public function assets(array $body = [], int $pageIndex = 1, int $pageSize = 50): JoblogicPage
    {
        return $this->search('Asset/GetAll', $body, $pageIndex, $pageSize);
    }

    /** @param array<string, mixed> $body */
    public function jobs(array $body = [], int $pageIndex = 1, int $pageSize = 50): JoblogicPage
    {
        return $this->search('Job/getall', $body, $pageIndex, $pageSize);
    }

    /** @param array<string, mixed> $body */
    public function jobAssets(array $body = [], int $pageIndex = 1, int $pageSize = 50): JoblogicPage
    {
        return $this->search('JobAsset/GetAll', $body, $pageIndex, $pageSize);
    }

    /** @param array<string, mixed> $body */
    public function visits(array $body = [], int $pageIndex = 1, int $pageSize = 50): JoblogicPage
    {
        return $this->search('Visit/GetAll', $body, $pageIndex, $pageSize);
    }

    /** @param array<string, mixed> $body */
    public function quotes(array $body = [], int $pageIndex = 1, int $pageSize = 50): JoblogicPage
    {
        return $this->search('Quote/GetAll', $body, $pageIndex, $pageSize);
    }

    /**
     * Retrieve the tenant task library used by Joblogic jobs.
     *
     * @param  array<string, mixed>  $body
     */
    public function tasks(array $body = [], int $pageIndex = 1, int $pageSize = 50): JoblogicPage
    {
        return $this->search('Task/GetAll', $body, $pageIndex, $pageSize);
    }

    /**
     * Retrieve one task-library definition by its provider GUID.
     */
    public function task(string $taskId): JoblogicResponse
    {
        return $this->get('Task/GetById', [
            'tenantId' => $this->credentials->tenantId,
            'id' => $taskId,
        ]);
    }

    /**
     * Retrieve the task completion history assigned to a Joblogic job.
     */
    public function jobTask(string $jobUniqueId): JoblogicResponse
    {
        return $this->get('JobTask', [
            'tenantId' => $this->credentials->tenantId,
            'uniqueId' => $jobUniqueId,
        ]);
    }

    /**
     * Retrieve the cost and profitability groups attached to a Joblogic job.
     */
    public function jobCosts(string|int $jobId): JoblogicResponse
    {
        return $this->get('jobcost', [
            'tenantId' => $this->credentials->tenantId,
            'Id' => $jobId,
        ]);
    }

    /** @param array<string, mixed> $body */
    public function invoices(array $body = [], int $pageIndex = 1, int $pageSize = 50): JoblogicPage
    {
        return $this->search('Invoice/GetAll', $body, $pageIndex, $pageSize);
    }

    /**
     * Search the provider's parts and equipment catalogue.
     *
     * @param  array<string, mixed>  $body
     */
    public function parts(array $body = [], int $pageIndex = 1, int $pageSize = 50): JoblogicPage
    {
        return $this->search('Part/GetAll', $body, $pageIndex, $pageSize);
    }

    /**
     * Search the provider's part-category catalogue.
     *
     * @param  array<string, mixed>  $body
     */
    public function partCategories(array $body = [], int $pageIndex = 1, int $pageSize = 50): JoblogicPage
    {
        return $this->search('PartCategory/GetAll', $body, $pageIndex, $pageSize);
    }

    /**
     * Search the provider's supplier catalogue.
     *
     * @param  array<string, mixed>  $body
     */
    public function suppliers(array $body = [], int $pageIndex = 1, int $pageSize = 50): JoblogicPage
    {
        return $this->search('Supplier/GetAll', $body, $pageIndex, $pageSize);
    }

    /**
     * Search Joblogic engineers, including their working-hour records when
     * requested by the caller.
     *
     * @param  array<string, mixed>  $body
     */
    public function engineers(array $body = [], int $pageIndex = 1, int $pageSize = 50): JoblogicPage
    {
        return $this->search('Engineer/GetAll', $body, $pageIndex, $pageSize);
    }

    /**
     * Search the provider's staff directory.
     *
     * @param  array<string, mixed>  $body
     */
    public function staff(array $body = [], int $pageIndex = 1, int $pageSize = 50): JoblogicPage
    {
        return $this->search('staff/GetAll', $body, $pageIndex, $pageSize);
    }

    /**
     * Search the provider's vehicle register.
     *
     * @param  array<string, mixed>  $body
     */
    public function vehicles(array $body = [], int $pageIndex = 1, int $pageSize = 50): JoblogicPage
    {
        return $this->search('vehicle/getall', $body, $pageIndex, $pageSize);
    }

    /**
     * Read timesheets for a provider date window. Joblogic documents a
     * maximum seven-day search window, so the caller owns window iteration.
     *
     * @param  array<string, mixed>  $body
     */
    public function timesheets(array $body = []): JoblogicResponse
    {
        return $this->searchResponse('Timesheet/GetAll', $body);
    }

    public function partAttributes(string|int $partId): JoblogicResponse
    {
        return $this->get('PartAttribute/GetByPartId', [
            'partId' => $partId,
            'tenantId' => $this->credentials->tenantId,
        ]);
    }

    public function customerAttributes(string|int $customerId): JoblogicResponse
    {
        return $this->get('CustomerAttribute/GetByCustomerId', [
            'customerId' => $customerId,
            'tenantId' => $this->credentials->tenantId,
        ]);
    }

    public function siteAttributes(string|int $siteId): JoblogicResponse
    {
        return $this->get('SiteAttribute/GetBySiteId', [
            'siteId' => $siteId,
            'tenantId' => $this->credentials->tenantId,
        ]);
    }

    public function jobAttributes(string|int $jobId): JoblogicResponse
    {
        return $this->get('JobAttribute/GetByJobId', [
            'jobId' => $jobId,
            'tenantId' => $this->credentials->tenantId,
        ]);
    }

    public function assetAttributes(string|int $assetId): JoblogicResponse
    {
        return $this->get('AssetAttribute/GetByAssetId', [
            'assetId' => $assetId,
            'tenantId' => $this->credentials->tenantId,
        ]);
    }

    /**
     * Retrieve one purchase order and its line items.
     *
     * Joblogic documents purchase orders as a detail endpoint rather than a
     * tenant-wide collection search. Callers should discover IDs from a
     * supported parent resource (for example, Job/getall) before using this
     * method.
     */
    public function purchaseOrder(string|int $purchaseOrderId): JoblogicResponse
    {
        return $this->get('PurchaseOrder', [
            'id' => $purchaseOrderId,
            'tenantId' => $this->credentials->tenantId,
        ]);
    }

    /**
     * Retrieve one purchase-order line item.
     */
    public function purchaseOrderLine(string|int $purchaseOrderLineId): JoblogicResponse
    {
        return $this->get('purchaseorder/getpoitem', [
            'id' => $purchaseOrderLineId,
            'tenantId' => $this->credentials->tenantId,
        ]);
    }

    /**
     * Retrieve the documented quote cost groups, including material, labour,
     * expense, travel, subcontractor and schedule-of-rates lines.
     */
    public function quoteCosts(string|int $quoteId): JoblogicResponse
    {
        return $this->get('Quote/GetCosts', [
            'quoteId' => $quoteId,
            'TenantId' => $this->credentials->tenantId,
        ]);
    }

    /**
     * Execute a provider GET request and return a framework-independent response.
     *
     * @param  array<string, mixed>  $query
     */
    public function get(string $path, array $query = []): JoblogicResponse
    {
        return JoblogicResponse::fromSaloon($this->send(new GetRequest($path, $query)));
    }

    /**
     * Request the provider-issued upload URI used by the note/attachment
     * workflow. Joblogic's public documentation currently spells the tenant
     * query parameter as `tenanId`; keep that provider contract here rather
     * than leaking it into application code.
     */
    public function getUploadFileUri(string $fileName): JoblogicResponse
    {
        return $this->get('File/GetUploadFileUri', [
            'fileName' => $fileName,
            'tenanId' => $this->credentials->tenantId,
        ]);
    }

    /**
     * Upload bytes to the secure URI returned by Joblogic.
     *
     * The upload URI is provider-issued and is intentionally sent through a
     * connector without the API bearer token. Callers should validate the
     * URI's origin and expiry before using it when the value comes from an
     * untrusted source.
     *
     * @param  resource|StreamInterface  $body
     */
    public function uploadFile(string $uploadUri, mixed $body, string $contentType = 'application/pdf'): JoblogicResponse
    {
        if (! $body instanceof StreamInterface && ! is_resource($body)) {
            throw new \InvalidArgumentException('Joblogic upload bodies must be a stream resource or PSR stream.');
        }

        $connector = new JoblogicUploadConnector;

        if (($mockClient = $this->getMockClient()) !== null) {
            $connector->withMockClient($mockClient);
        }

        return JoblogicResponse::fromSaloon($connector->send(
            new UploadFileRequest($uploadUri, $body, $contentType),
        ));
    }

    /**
     * Create a provider note and optional attachment on an entity.
     *
     * The tenant boundary is always supplied by the credentials, so a caller
     * cannot accidentally write a note to another Joblogic tenant.
     *
     * @param  array<string, mixed>  $payload
     */
    public function createNote(array $payload): JoblogicResponse
    {
        $payload['TenantId'] = $this->credentials->tenantId;

        return JoblogicResponse::fromSaloon($this->send(new CreateNoteRequest($payload)));
    }

    /**
     * Execute a provider collection search whose response is not paginated by
     * the standard Items/TotalCount envelope.
     *
     * @param  array<string, mixed>  $body
     */
    public function searchResponse(string $path, array $body = []): JoblogicResponse
    {
        $body['TenantId'] = $this->credentials->tenantId;

        return JoblogicResponse::fromSaloon($this->send(new SearchRequest($path, $body)));
    }
}
