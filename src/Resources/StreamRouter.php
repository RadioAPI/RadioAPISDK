<?php

declare(strict_types=1);

namespace RadioApi\Resources;

use RadioApi\Client;
use RadioApi\Exceptions\InvalidResponseException;

final readonly class StreamRouter
{
    public function __construct(private Client $client) {}

    /**
     * List Stream Router routes for the API key's account.
     *
     * @param  array{search?: string, status?: 'active'|'inactive', station?: string, sort?: 'created_at'|'slug', direction?: 'asc'|'desc', per_page?: int}  $query
     * @return array{data: list<array<string, mixed>>, links: array<string, mixed>, meta: array<string, mixed>}
     */
    public function list(array $query = []): array
    {
        $response = $this->client->managementRequest('GET', 'stream-links', $query);

        if ($response === null) {
            throw new InvalidResponseException('RadioAPI returned an empty Stream Router list response.');
        }

        return $response;
    }

    /**
     * Create a public route to a station or direct stream URL.
     *
     * @param  array{slug: string, station_uuid?: string, target_url?: string, redirect_status_code?: 302|307}  $attributes
     * @return array<string, mixed>
     */
    public function create(array $attributes): array
    {
        return $this->data($this->client->managementRequest('POST', 'stream-links', payload: $attributes));
    }

    /** @return array<string, mixed> */
    public function find(string $routeUuid): array
    {
        return $this->data($this->client->managementRequest('GET', 'stream-links/'.rawurlencode($routeUuid)));
    }

    /**
     * Update a route's slug, destination, state, or redirect status.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public function update(string $routeUuid, array $attributes): array
    {
        return $this->data($this->client->managementRequest(
            'PATCH',
            'stream-links/'.rawurlencode($routeUuid),
            payload: $attributes,
        ));
    }

    /** Disable a Stream Router route. */
    public function delete(string $routeUuid): void
    {
        $this->client->managementRequest('DELETE', 'stream-links/'.rawurlencode($routeUuid));
    }

    /**
     * @param  array<string, mixed>|null  $response
     * @return array<string, mixed>
     */
    private function data(?array $response): array
    {
        if (! is_array($response['data'] ?? null)) {
            throw new InvalidResponseException('RadioAPI returned an invalid Stream Router response.');
        }

        return $response['data'];
    }
}
