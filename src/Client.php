<?php

declare(strict_types=1);

namespace RadioApi;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use JsonException;
use RadioApi\Exceptions\ApiException;
use RadioApi\Exceptions\InvalidResponseException;
use RadioApi\Exceptions\TransportException;
use RadioApi\Resources\Stations;
use RadioApi\Resources\StreamRouter;

final readonly class Client
{
    private ClientInterface $httpClient;

    public function __construct(
        private string   $baseUrl,
        private ?string  $apiKey = null,
        ?ClientInterface $httpClient = null,
    ) {
        $this->httpClient = $httpClient ?? new GuzzleClient;
    }

    public static function make(
        string $baseUrl,
        ?string $apiKey = null,
        ?ClientInterface $httpClient = null,
    ): self {
        return new self($baseUrl, $apiKey, $httpClient);
    }

    public static function forPublicNowPlaying(
        string $baseUrl,
        ?ClientInterface $httpClient = null,
    ): self {
        return new self($baseUrl, null, $httpClient);
    }

    public function stations(): Stations
    {
        return new Stations($this);
    }

    public function streamRouter(): StreamRouter
    {
        return new StreamRouter($this);
    }

    /**
     * Get the current metadata for a station.
     *
     * @return array<string, mixed>
     */
    public function nowPlaying(string $stationUuid): array
    {
        $response = $this->request(
            'GET',
            $this->baseUrl('/stations/'.rawurlencode($stationUuid).'/nowplaying'),
        );

        if ($response === null) {
            throw new InvalidResponseException('RadioAPI returned an empty Now Playing response.');
        }

        return $response;
    }

    /**
     * @internal Used by the resource helpers returned from this client.
     *
     * @param  array<string, bool|float|int|string|null>  $query
     * @param  array<string, mixed>|null  $payload
     * @return array<string, mixed>|null
     */
    public function managementRequest(
        string $method,
        string $path,
        array $query = [],
        ?array $payload = null,
    ): ?array {
        $url = $this->managementUrl($path);

        if ($query !== []) {
            $url .= '?'.http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        }

        return $this->request($method, $url, $payload);
    }

    private function managementUrl(string $path): string
    {
        return rtrim($this->baseUrl, '/').'/api/v1/'.ltrim($path, '/');
    }

    private function baseUrl(string $path): string
    {
        return rtrim($this->baseUrl, '/').$path;
    }

    /**
     * @param  array<string, mixed>|null  $payload
     * @return array<string, mixed>|null
     */
    private function request(string $method, string $url, ?array $payload = null): ?array
    {
        $options = ['headers' => ['Accept' => 'application/json']];

        if ($this->apiKey !== null && $this->apiKey !== '') {
            $options['headers']['Authorization'] = 'Bearer '.$this->apiKey;
        }

        if ($payload !== null) {
            $options['json'] = $payload;
        }

        $options['http_errors'] = false;

        try {
            $response = $this->httpClient->request($method, $url, $options);
            $body = (string) $response->getBody();
        } catch (GuzzleException $exception) {
            throw new TransportException('RadioAPI could not be reached.', previous: $exception);
        }

        if ($response->getStatusCode() === 204) {
            return null;
        }

        $decoded = $this->decode($body);

        if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
            $error = $decoded['error'] ?? [];

            throw new ApiException(
                $response->getStatusCode(),
                is_string($error['code'] ?? null) ? $error['code'] : null,
                is_string($error['message'] ?? null)
                    ? $error['message']
                    : 'RadioAPI rejected the request.',
                $decoded,
                $response->getHeaders(),
            );
        }

        return $decoded;
    }

    /** @return array<string, mixed> */
    private function decode(string $body): array
    {
        if ($body === '') {
            throw new InvalidResponseException('RadioAPI returned an empty JSON response.');
        }

        try {
            $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidResponseException('RadioAPI returned invalid JSON.', previous: $exception);
        }

        if (! is_array($decoded)) {
            throw new InvalidResponseException('RadioAPI returned a JSON value instead of an object.');
        }

        return $decoded;
    }
}
