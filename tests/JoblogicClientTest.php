<?php

use ChrisJohnLeah\Joblogic\Data\JoblogicCredentials;
use ChrisJohnLeah\Joblogic\JoblogicClient;
use ChrisJohnLeah\Joblogic\JoblogicTokenConnector;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

function packageJoblogicCredentials(): JoblogicCredentials
{
    return JoblogicCredentials::forEnvironment('client', 'secret', 'tenant', 'uat');
}

it('authenticates with the documented Joblogic client-credentials scope', function () {
    $mock = new MockClient([
        MockResponse::make(['access_token' => 'token', 'expires_in' => 3600]),
    ]);

    $authenticator = (new JoblogicTokenConnector(packageJoblogicCredentials()))
        ->withMockClient($mock)
        ->getAccessToken();

    expect($authenticator->getAccessToken())->toBe('token')
        ->and($mock->getLastPendingRequest()?->getUrl())->toBe('https://uatidentityserver.joblogic.com/connect/token');
});

it('keeps the tenant and page boundary on a search request', function () {
    $mock = new MockClient([
        MockResponse::make(['Items' => [['Id' => 42]], 'TotalCount' => 2]),
    ]);

    $page = (new JoblogicClient(packageJoblogicCredentials(), 'token'))
        ->withMockClient($mock)
        ->search('Customer/GetAll', ['SearchTerm' => 'doors'], pageIndex: 2, pageSize: 1);

    expect($page->items)->toHaveCount(1)
        ->and($page->totalCount)->toBe(2)
        ->and($mock->getLastPendingRequest()?->getRequest()->body()->all())->toBe([
            'TenantId' => 'tenant',
            'PageIndex' => 2,
            'PageSize' => 1,
            'SearchTerm' => 'doors',
        ]);
});

it('exposes named helpers for the documented collection searches', function () {
    foreach ([
        ['customers', 'Customer/GetAll'],
        ['contacts', 'Contact/GetAll'],
        ['sites', 'Site/GetAll'],
        ['assets', 'Asset/GetAll'],
        ['jobs', 'Job/getall'],
        ['jobAssets', 'JobAsset/GetAll'],
        ['visits', 'Visit/GetAll'],
        ['quotes', 'Quote/GetAll'],
        ['tasks', 'Task/GetAll'],
        ['invoices', 'Invoice/GetAll'],
    ] as [$method, $path]) {
        $mock = new MockClient([
            MockResponse::make(['Items' => [], 'TotalCount' => 0]),
        ]);

        (new JoblogicClient(packageJoblogicCredentials(), 'token'))
            ->withMockClient($mock)
            ->{$method}();

        expect($mock->getLastPendingRequest()?->getUrl())->toBe('https://uatapi.joblogic.com/api/v1/'.$path);
    }
});

it('retrieves the documented quote cost groups with the tenant boundary', function () {
    $mock = new MockClient([
        MockResponse::make([
            'GroupLinesResponse' => [
                'MaterialLines' => [],
                'LabourLines' => [],
            ],
        ]),
    ]);

    $response = (new JoblogicClient(packageJoblogicCredentials(), 'token'))
        ->withMockClient($mock)
        ->quoteCosts('quote-1');

    expect($response->successful())->toBeTrue()
        ->and($response->json())->toBe([
            'GroupLinesResponse' => [
                'MaterialLines' => [],
                'LabourLines' => [],
            ],
        ])
        ->and($mock->getLastPendingRequest()?->getRequest()->query()->all())
        ->toBe(['quoteId' => 'quote-1', 'TenantId' => 'tenant']);
});

it('retrieves task-library and job-task history with the documented identifiers', function () {
    $mock = new MockClient([
        MockResponse::make(['Id' => 'task-1', 'Task' => 'Inspect door']),
        MockResponse::make(['JobId' => 'job-1', 'JobTasks' => []]),
        MockResponse::make(['TotalCostIncludingVAT' => 120.00]),
    ]);
    $client = (new JoblogicClient(packageJoblogicCredentials(), 'token'))
        ->withMockClient($mock);

    $task = $client->task('task-1');

    expect($task->json('Task'))->toBe('Inspect door')
        ->and($mock->getLastPendingRequest()?->getRequest()->query()->all())
        ->toBe(['tenantId' => 'tenant', 'id' => 'task-1']);

    $jobTask = $client->jobTask('job-1');

    expect($jobTask->json('JobId'))->toBe('job-1')
        ->and($mock->getLastPendingRequest()?->getRequest()->query()->all())
        ->toBe(['tenantId' => 'tenant', 'uniqueId' => 'job-1']);

    $jobCosts = $client->jobCosts('job-1');

    expect($jobCosts->json('TotalCostIncludingVAT'))->toBe(120)
        ->and($mock->getLastPendingRequest()?->getRequest()->query()->all())
        ->toBe(['tenantId' => 'tenant', 'Id' => 'job-1']);
});

it('retrieves a purchase order with the documented tenant and id query', function () {
    $mock = new MockClient([
        MockResponse::make([
            'Id' => 'po-1',
            'PONumber' => 'PO-1001',
            'Lines' => [['Id' => 'line-1']],
        ]),
    ]);

    $response = (new JoblogicClient(packageJoblogicCredentials(), 'token'))
        ->withMockClient($mock)
        ->purchaseOrder('po-1');

    expect($response->successful())->toBeTrue()
        ->and($response->json('PONumber'))->toBe('PO-1001')
        ->and($mock->getLastPendingRequest()?->getRequest()->query()->all())
        ->toBe(['id' => 'po-1', 'tenantId' => 'tenant']);
});

it('retrieves a purchase-order line with the documented endpoint', function () {
    $mock = new MockClient([
        MockResponse::make(['Id' => 'line-1', 'Description' => 'Closer']),
    ]);

    $response = (new JoblogicClient(packageJoblogicCredentials(), 'token'))
        ->withMockClient($mock)
        ->purchaseOrderLine('line-1');

    expect($response->json('Description'))->toBe('Closer')
        ->and($mock->getLastPendingRequest()?->getRequest()->query()->all())
        ->toBe(['id' => 'line-1', 'tenantId' => 'tenant']);
});

it('requests the documented Joblogic upload URI with the provider tenant parameter', function () {
    $mock = new MockClient([
        MockResponse::make([
            'Uri' => 'https://uploads.example.test/blob?sas=token',
            'ExpiryDate' => '2026-08-17T12:00:00Z',
            'FileName' => 'form.pdf',
        ]),
    ]);

    $response = (new JoblogicClient(packageJoblogicCredentials(), 'token'))
        ->withMockClient($mock)
        ->getUploadFileUri('form.pdf');

    expect($response->json('Uri'))->toBe('https://uploads.example.test/blob?sas=token')
        ->and($mock->getLastPendingRequest()?->getRequest()->query()->all())
        ->toBe(['fileName' => 'form.pdf', 'tenanId' => 'tenant']);
});

it('uploads a PDF to the provider-issued URI without sending the API bearer token', function () {
    $mock = new MockClient([
        MockResponse::make([], 201),
    ]);
    $body = fopen('php://temp', 'r+');
    fwrite($body, '%PDF-test');
    rewind($body);

    $response = (new JoblogicClient(packageJoblogicCredentials(), 'token'))
        ->withMockClient($mock)
        ->uploadFile('https://uploads.example.test/blob?sas=token', $body);

    $request = $mock->getLastPendingRequest();
    $requestBody = $request?->getRequest()->body()->get();
    rewind($requestBody);

    expect($response->status)->toBe(201)
        ->and($request?->getUrl())->toBe('https://uploads.example.test/blob?sas=token')
        ->and(stream_get_contents($requestBody))->toBe('%PDF-test')
        ->and($request?->getRequest()->headers()->get('x-ms-blob-type'))->toBe('BlockBlob')
        ->and($request?->getRequest()->headers()->get('Content-Type'))->toBe('application/pdf')
        ->and($request?->getRequest()->headers()->get('Authorization'))->toBeNull();
});

it('creates a Joblogic note while enforcing the client tenant boundary', function () {
    $mock = new MockClient([
        MockResponse::make(['NoteId' => 'note-1', 'EntityType' => 3]),
    ]);

    $response = (new JoblogicClient(packageJoblogicCredentials(), 'token'))
        ->withMockClient($mock)
        ->createNote([
            'EntityId' => 'job-1',
            'EntityType' => 3,
            'NoteText' => 'DoorOps form report',
            'TenantId' => 'wrong-tenant',
            'Attachments' => [
                ['AttachmentLink' => 'https://uploads.example.test/blob?sas=token', 'Name' => 'form.pdf'],
            ],
        ]);

    expect($response->json('NoteId'))->toBe('note-1')
        ->and($mock->getLastPendingRequest()?->getRequest()->body()->all())
        ->toMatchArray([
            'EntityId' => 'job-1',
            'EntityType' => 3,
            'NoteText' => 'DoorOps form report',
            'TenantId' => 'tenant',
        ]);
});
