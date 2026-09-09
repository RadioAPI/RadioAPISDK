<?php

declare(strict_types=1);

namespace RadioApi\Resources;

use RadioApi\Client;
use RadioApi\Exceptions\InvalidResponseException;

final readonly class Stations
{
    public function __construct(private Client $client) {}

    /**
     * List stations for the API key's account.
     *
     * @param  array{search?: string, provider?: string, status?: 'active'|'inactive', sort?: 'created_at'|'name', direction?: 'asc'|'desc', per_page?: int}  $query
     * @return array{data: list<array<string, mixed>>, links: array<string, mixed>, meta: array<string, mixed>}
     */
    public function list(array $query = []): array
    {
        $response = $this->client->managementRequest('GET', 'stations', $query);

        if ($response === null) {
            throw new InvalidResponseException('RadioAPI returned an empty station list response.');
        }

        return $response;
    }

    /**
     * Create a station.
     *
     * @param  array{name: string, stream_url: string, stream_metadata_provider?: 'jcplayer'|'azuracast'|'radioking'|'live365', stream_metadata_settings?: array{url: string, genre?: string}, music_provider?: string, nowplaying_access?: 'public'|'private', nowplaying_history_enabled?: bool, slug?: string}  $attributes
     * @return array<string, mixed>
     */
    public function create(array $attributes): array
    {
        return $this->data($this->client->managementRequest('POST', 'stations', payload: $attributes));
    }

    /** @return array<string, mixed> */
    public function find(string $stationUuid): array
    {
        return $this->data($this->client->managementRequest('GET', 'stations/'.rawurlencode($stationUuid)));
    }

    /**
     * Update any station fields accepted by the API.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public function update(string $stationUuid, array $attributes): array
    {
        return $this->data($this->client->managementRequest(
            'PATCH',
            'stations/'.rawurlencode($stationUuid),
            payload: $attributes,
        ));
    }

    /** Disable a station. */
    public function delete(string $stationUuid): void
    {
        $this->client->managementRequest('DELETE', 'stations/'.rawurlencode($stationUuid));
    }

    /**
     * @param  array<string, mixed>|null  $response
     * @return array<string, mixed>
     */
    private function data(?array $response): array
    {
        if (! is_array($response['data'] ?? null)) {
            throw new InvalidResponseException('RadioAPI returned an invalid station response.');
        }

        return $response['data'];
    }
}
