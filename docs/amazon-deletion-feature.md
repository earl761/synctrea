# Amazon Product Deletion Feature

This document describes the automatic Amazon product deletion feature that triggers when a product's catalog status changes from 'in_catalog' to 'queued'.

## Overview

When a ConnectionPairProduct's `catalog_status` changes from `in_catalog` to `queued`, the system automatically initiates a deletion request to Amazon to remove/delist the product from the Amazon catalog.

## Implementation Details

### Observer Pattern

The feature is implemented using Laravel's Observer pattern in the `ConnectionPairProductObserver` class:

**File:** `app/Observers/ConnectionPairProductObserver.php`

### Key Components

1. **Status Change Detection**
   - Monitors changes to the `catalog_status` field
   - Specifically detects transitions from `in_catalog` to `queued`
   - Uses Laravel's `isDirty()` and `getOriginal()` methods

2. **Amazon API Integration**
   - Uses the existing `AmazonApiClient` class
   - Calls the `deleteFromSellerCatalog()` method
   - Handles API errors gracefully with logging

3. **Connection Type Validation**
   - Only processes Amazon connections (`destination_type === 'amazon'`)
   - Skips non-Amazon connections to avoid unnecessary processing

### Code Flow

```php
// In ConnectionPairProductObserver::updated()
if ($connectionPairProduct->isDirty('catalog_status') &&
    $connectionPairProduct->getOriginal('catalog_status') === ConnectionPairProduct::STATUS_IN_CATALOG &&
    $connectionPairProduct->catalog_status === ConnectionPairProduct::STATUS_QUEUED) {
    $this->handleAmazonDeletion($connectionPairProduct);
}
```

### Error Handling

- **Logging**: All operations are logged with appropriate levels (info, warning, error)
- **Graceful Degradation**: Failures don't prevent other operations
- **Validation**: Checks for required relationships (connectionPair, product)
- **Exception Handling**: Catches and logs all exceptions

### Logging Details

The feature logs the following events:

1. **Info Logs**:
   - Initiation of deletion process
   - Successful deletion requests
   - Skipping non-Amazon connections

2. **Warning Logs**:
   - Missing connection pairs or products
   - Failed deletion attempts (false results)

3. **Error Logs**:
   - Exception details with stack traces
   - API communication failures

## Usage

### Automatic Triggering

The feature automatically triggers when:

```php
// Any of these operations will trigger the observer
$connectionPairProduct->update(['catalog_status' => 'queued']);
$connectionPairProduct->catalog_status = 'queued';
$connectionPairProduct->save();
```

### Manual Testing

To test the feature:

```php
// Find a product with 'in_catalog' status
$product = ConnectionPairProduct::where('catalog_status', 'in_catalog')
    ->whereHas('connectionPair', function($query) {
        $query->where('destination_type', 'amazon');
    })
    ->first();

// Change status to trigger deletion
$product->update(['catalog_status' => 'queued']);

// Check logs for deletion attempt details
```

## Prerequisites

1. **Amazon API Configuration**: Ensure Amazon API credentials are properly configured
2. **Connection Pairs**: Products must be associated with Amazon connection pairs
3. **Observer Registration**: The `ConnectionPairProductObserver` must be registered in `AppServiceProvider`

## Monitoring

### Log Files

Monitor the following log entries:

```
[INFO] Initiating Amazon deletion for product status change
[INFO] Successfully initiated Amazon deletion
[WARNING] Amazon deletion returned false result
[ERROR] Failed to delete product from Amazon
```

### Database Changes

The feature only reads from the database and makes API calls. It doesn't modify the product record beyond the status change that triggered it.

## Related Files

- `app/Observers/ConnectionPairProductObserver.php` - Main implementation
- `app/Services/Api/AmazonApiClient.php` - Amazon API integration
- `app/Models/ConnectionPairProduct.php` - Product model with status constants
- `app/Console/Commands/AmazonCatalogCleanupCommand.php` - Related cleanup functionality

## Future Enhancements

Potential improvements:

1. **Batch Processing**: Handle multiple products in bulk
2. **Retry Logic**: Implement automatic retries for failed deletions
3. **Status Tracking**: Add fields to track deletion status and timestamps
4. **Webhooks**: Add webhook notifications for deletion events
5. **Queue Integration**: Use Laravel queues for asynchronous processing