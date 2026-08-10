# Joblogic API

Framework-agnostic PHP SDK for the Joblogic API, built on Saloon 4.

This package deliberately stops at the provider boundary. It owns OAuth2
client-credentials authentication, typed paginated search responses, GET
responses, bearer authentication, and retries for rate limits and transient
server errors. It does not know about Laravel, tenants, plans, queues,
migrations, local models, or archive retention.

```php
use ChrisJohnLeah\Joblogic\Data\JoblogicCredentials;
use ChrisJohnLeah\Joblogic\JoblogicClient;

$credentials = JoblogicCredentials::forEnvironment(
    clientId: $clientId,
    clientSecret: $clientSecret,
    tenantId: $tenantId,
    environment: 'uat',
);

$client = JoblogicClient::fromClientCredentials($credentials);
$page = $client->search('Customer/GetAll', [
    'SearchTerm' => '',
    'SearchCondition' => 0,
    'IncludeInactive' => true,
]);

foreach ($page->items as $customer) {
    // Provider data only: map it in the consuming application.
}

$site = $client->get('Site/GetById', [
    'tenantId' => $tenantId,
    'id' => 42,
]);
```

JoblogicPage and JoblogicResponse expose the HTTP status, successful() /
failed() helpers, original provider JSON, and pagination metadata. The
client's search() method is intended for the provider's POST-based collection
operations; customers(), contacts(), sites(), assets(), jobs(), jobAssets(),
visits(), quotes(), invoices(), parts(), partCategories(), suppliers(),
engineers(), staff(), and vehicles() cover the documented paginated collection
searches. timesheets() covers the documented non-paginated seven-day time
window; the customerAttributes(), siteAttributes(), jobAttributes(),
assetAttributes(), and partAttributes() helpers cover typed attribute reads;
purchaseOrder(), purchaseOrderLine(), and get() cover detail and child reads.
Joblogic documents purchase-order detail retrieval by ID, not
a tenant-wide purchase-order collection, so callers should discover IDs from
supported parent responses before calling purchaseOrder(). The SDK does not
silently iterate timesheet windows because the consuming application must own
its date range and checkpoint policy.

The documented Joblogic API requires the consumer's egress IP to be allowlisted
and uses the JL.Api OAuth scope. UAT and production endpoints are selected
explicitly by the credentials object.

## Testing

Run the package contract tests with `composer install` followed by
`vendor/bin/pest`.
