# Newegg Existing Item Creation Feed

This document explains how to use the Newegg existing item creation feed functionality to add items to your Newegg marketplace seller account by matching against existing items in Newegg's catalog.

## Overview

The existing item creation feed allows you to create listings for items that already exist in Newegg's catalog. Instead of providing full product details, you match against existing items using identifiers like:

- **UPC (Universal Product Code)**
- **MPN (Manufacturer Part Number)**
- **Newegg Item Number**

## Setup

### 1. Create Newegg Destination

First, create a Newegg destination with your API credentials:

```php
php artisan tinker

$destination = new App\Models\Destination();
$destination->name = 'My Newegg Store';
$destination->type = 'newegg';
$destination->api_key = 'your-api-key';
$destination->secret_key = 'your-secret-key';
$destination->seller_id = 'your-seller-id';
$destination->api_endpoint = 'https://api.newegg.com';
$destination->is_active = true;
$destination->save();

echo "Destination created with ID: " . $destination->id;
```

### 2. Prepare Item Data

Create a JSON file with your items or use the test functionality. Each item must include:

**Required Fields:**
- `sku`: Your unique SKU for the item
- `manufacturer`: Manufacturer name
- At least one identifier: `manufacturer_part_number`, `upc`, or `newegg_item_number`

**Optional Fields:**
- `price`: Selling price
- `quantity`: Available inventory
- `shipping`: Shipping option ("Default", "Free", etc.)
- `condition`: Item condition ("New", "Refurbished", etc.)
- `activation_mark`: "True" or "False"
- `msrp`: Manufacturer's suggested retail price
- `map`: Minimum advertised price
- `checkout_map`: Boolean for checkout MAP enforcement
- `warranty`: Warranty information

## Usage

### Submit Test Item

To test the functionality with a single test item:

```bash
php artisan newegg:submit-existing-item-feed --destination-id=1 --test-item
```

### Submit Items from JSON File

To submit multiple items from a JSON file:

```bash
php artisan newegg:submit-existing-item-feed --destination-id=1 --file=sample-newegg-items.json
```

### Sample JSON Format

See `sample-newegg-items.json` for example item data:

```json
[
  {
    "sku": "SAMPLE-ITEM-001",
    "manufacturer": "Samsung",
    "manufacturer_part_number": "MZ-V8V1T0B/AM",
    "upc": "887276347486",
    "price": 129.99,
    "quantity": 25,
    "shipping": "Default",
    "condition": "New",
    "activation_mark": "True",
    "msrp": 149.99,
    "warranty": "Manufacturer Warranty"
  }
]
```

## API Implementation

The implementation follows Newegg's official API documentation:

- **Endpoint**: `POST /marketplace/datafeedmgmt/feeds/submitfeed`
- **Feed Type**: `EXISTING_ITEM_CREATION_FEED`
- **Authentication**: `Authorization` and `SecretKey` headers
- **Content**: XML format with item data

### Key Features

1. **Automatic XML Generation**: The system converts JSON item data to Newegg's required XML format
2. **Validation**: Ensures required fields are present before submission
3. **Error Handling**: Provides clear error messages for debugging
4. **Request Tracking**: Returns Request ID for feed status monitoring

## Troubleshooting

### Common Issues

1. **401 Authentication Error**
   - Verify API key and secret key are correct
   - Ensure seller ID matches your Newegg account

2. **404 Endpoint Error**
   - Check that the API endpoint is correct: `https://api.newegg.com`
   - Verify the feed submission endpoint is available

3. **Validation Errors**
   - Ensure all required fields are provided
   - Check that at least one identifier (UPC, MPN, or Newegg Item Number) is included
   - Verify data formats match Newegg's requirements

### Testing Without Credentials

You can test the command structure and validation without valid credentials:

```bash
# This will validate the JSON structure and show what would be sent
php artisan newegg:submit-existing-item-feed --destination-id=1 --test-item
```

The command will fail at the API call stage but will show you the generated request structure.

## Next Steps

1. **Feed Status Monitoring**: Implement feed status checking using the returned Request ID
2. **Bulk Processing**: Add support for processing large CSV files
3. **Error Recovery**: Implement retry logic for failed submissions
4. **Inventory Sync**: Add automated inventory synchronization

## References

- [Newegg Marketplace API Documentation](https://developer.newegg.com/)
- [Existing Item Creation Feed Documentation](https://developer.newegg.com/newegg_marketplace_api/datafeed_management/submit_feed/existing_item_creation_feed/)