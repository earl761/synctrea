<?php

namespace App\Services\Api;

use App\Models\Destination;
use App\Models\Product;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\Api\AmazonSpApiSigner;

/**
 * Lightweight wrapper around Amazon SP‑API for catalog search,
 * single‑item updates (Listings Items API), and bulk updates
 * (JSON_LISTINGS_FEED).
 */
class AmazonSpApiClient
{
    public const CATALOG_API_VERSION        = '2022-04-01';
    public const LISTINGS_ITEMS_API_VERSION = '2021-08-01';
    public const FEEDS_API_VERSION          = '2021-06-30';

    private Destination $destination;
    private AmazonSpApiSigner $signer;
    private bool $sandbox;

    // public function __construct(
    //     Destination $destination,
    //     AmazonSpApiSigner $signer,
    //     bool $sandbox = false
    // ) {
    //     $this->destination = $destination;
    //     $this->signer      = $signer;
    //     $this->sandbox     = $sandbox;
    // }
    public function __construct(Destination $destination, ?AmazonSpApiSigner $signer = null, bool $sandbox = false)
    {
        $this->destination = $destination;
        $this->signer      = $signer ?? new AmazonSpApiSigner($destination);
        $this->sandbox     = $sandbox;
    }
    /* ───────────────────────────────────────────────────────────
     *  CATALOG SEARCH
     * ─────────────────────────────────────────────────────────── */

    public function searchCatalogItemByUpc(string $upc): ?array
    {
        $uri = "/catalog/" . self::CATALOG_API_VERSION . "/items";

        $query = [
            'identifiers'     => $upc,
            'identifiersType' => 'UPC',
            'marketplaceIds'  => $this->destination->marketplace_id,
        ];

        $resp = $this->signedGet($uri, $query);

        return $resp->status() === 200 ? $resp->json() : null;
    }

    /* ───────────────────────────────────────────────────────────
     *  FAST PER‑SKU UPDATE (Listings Items API)
     * ─────────────────────────────────────────────────────────── */

    public function updateSingleItem(Product $product): bool
    {
        $uri = sprintf(
            "/listings/%s/items/%s/%s",
            self::LISTINGS_ITEMS_API_VERSION,
            $this->destination->seller_id,
            $product->pivot->destination_sku
        );

        $payload  = $this->buildListingsItemsPayload($product);
        $response = $this->signedPut($uri, $payload);

        return in_array($response->status(), [200, 202], true);
    }

    /* ───────────────────────────────────────────────────────────
     *  BULK UPDATE (JSON_LISTINGS_FEED)
     * ─────────────────────────────────────────────────────────── */

    public function pushCatalogFeed(Product $product): string
    {
        $content        = $this->generateListingsFeedContent($product);   // gzipped JSON
        $docMeta        = $this->createFeedDocument('application/json; charset=UTF-8');
        $this->uploadFeedContent($docMeta['url'], $content, true);

        // NB: enum case below assumes you defined JSON_LISTINGS_FEED
        return $this->createFeed(
            AmazonFeedType::JSON_LISTINGS_FEED,
            $docMeta['feedDocumentId']
        );
    }

    /* ══════════════════════════════════════════════════════════
     *  INTERNAL HELPERS
     * ══════════════════════════════════════════════════════════ */

    /** Signed GET */
    private function signedGet(string $uri, array $query = [])
    {
        $signed = $this->signer->sign('GET', $uri, $query);

        return Http::withHeaders($signed->headers)->get($signed->url, $signed->query);
    }

    /** Signed PUT */
    private function signedPut(string $uri, array $body = [])
    {
        $signed = $this->signer->sign('PUT', $uri, [], $body);

        return Http::withHeaders($signed->headers)->json($signed->url, $body);
    }

    /** Signed POST */
    private function signedPost(string $uri, array $body = [])
    {
        $signed = $this->signer->sign('POST', $uri, [], $body);

        return Http::withHeaders($signed->headers)->json($signed->url, $body);
    }

    /* ────── Feed helpers ────── */

    private function createFeedDocument(string $contentType): array
    {
        $uri     = "/feeds/" . self::FEEDS_API_VERSION . "/documents";
        $payload = ['contentType' => $contentType];

        return $this->signedPost($uri, $payload)->json();
    }

    private function uploadFeedContent(string $presignedUrl, string $content, bool $gz = false): void
    {
        Http::withHeaders([
            'Content-Type'        => 'application/json; charset=UTF-8',
            'Content-MD5'         => base64_encode(md5($content, true)),
            'x-amz-content-sha256' => hash('sha256', $content),
            'Content-Encoding'    => $gz ? 'gzip' : null,
        ])->put($presignedUrl, $content);
    }

    private function createFeed(string $feedType, string $feedDocumentId): string
    {
        $uri = "/feeds/" . self::FEEDS_API_VERSION . "/feeds";

        $payload = [
            'feedType'            => $feedType,
            'marketplaceIds'      => [$this->destination->marketplace_id],
            'inputFeedDocumentId' => $feedDocumentId,
        ];

        return $this->signedPost($uri, $payload)->json('feedId');
    }

    /* ────── Payload builders ────── */

    /** Listings Items API PATCH */
    private function buildListingsItemsPayload(Product $product): array
    {
        return [
            'productType' => 'PRODUCT',
            'patches' => [
                [
                    'op'   => 'PUSH',
                    'path' => '/attributes',
                    'value' => [
                        'externalProductId' => [[
                            'value' => $product->upc,
                            'type'  => 'UPC',
                        ]],
                        'brand'       => [['value' => $product->brand]],
                        'itemName'    => [['value' => $product->name]],
                        'description' => [['value' => $product->description]],
                        // TODO: map bullets, images, dimensions, etc.
                    ],
                ],
            ],
        ];
    }

    /** JSON_LISTINGS_FEED payload – returned gzipped */
    private function generateListingsFeedContent(Product $product): string
    {
        $feed = [
            'header' => [
                'sellerId' => $this->destination->seller_id,
                'version'  => '2.0',
                'feedType' => 'JSON_LISTINGS_FEED',
            ],
            'messages' => [
                [
                    'messageId'    => 1,
                    'operationType'=> 'PartialUpdate',
                    'sku'          => $product->pivot->destination_sku,
                    'productType'  => 'PRODUCT',
                    'attributes'   => $this->buildListingsItemsPayload($product)['patches'][0]['value'],
                ],
            ],
        ];

        return gzencode(json_encode(
            $feed,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        ));
    }
}
