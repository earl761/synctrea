# Ingram Micro Integration

## SFTP Feed Integration

The system integrates with Ingram Micro's SFTP service to automatically download and process product price and inventory files.

### Configuration

1. Obtain SFTP credentials from Ingram Micro:
   - SFTP Host
   - Username
   - Password
   - File path (optional, defaults to `/PRICE.ZIP`)

2. Configure the supplier in the admin panel:
   - Set supplier type to `ingram_micro`
   - Add SFTP credentials in the encrypted credentials field:
     ```json
     {
       "sftp_host": "your-sftp-host",
       "sftp_username": "your-username",
       "sftp_password": "your-password",
       "sftp_path": "/optional/custom/path/PRICE.ZIP"
     }
     ```

### Feed Processing

The system automatically downloads and processes Ingram Micro's feed files daily at 6:00 AM. The process:

1. Downloads the ZIP file from SFTP
2. Extracts the price/inventory file
3. Updates product information in the database
4. Cleans up temporary files

### Manual Update

To manually trigger a feed update:

```bash
# Update all active Ingram Micro suppliers
php artisan ingram:feed-update

# Update specific connection pair
php artisan ingram:feed-update {connectionPairId}
```

### File Format

The price file contains the following fields:

- Ingram Part Number (SKU)
- Description
- Vendor Part Number
- UPC/EAN
- Weight
- Price
- Stock Quantity

### Error Handling

Errors during feed processing are:
- Logged to Laravel's error log
- Reported via the command output
- Tracked in the sync_logs table

### Monitoring

Monitor the feed updates through:
- Laravel's scheduled task output
- System logs
- Sync logs in the admin panel