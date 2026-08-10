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
