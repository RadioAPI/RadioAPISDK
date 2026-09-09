<?php

declare(strict_types=1);

namespace RadioApi\Tests;

use PHPUnit\Framework\TestCase;
use RadioApi\Client;
use RadioApi\Exceptions\ApiException;
use GuzzleHttp\Psr7\Response;

final class ClientTest extends TestCase
{
    public function test_it_creates_stations_with_the_management_api(): void
    {
        $transport = new FakeTransport([
            new Response(201, [], json_encode(['data' => ['uuid' => 'station-uuid', 'name' => 'Chillwave']], JSON_THROW_ON_ERROR)),
        ]);
        $client = new Client('https://radio.example', 'radio_live_secret', httpClient: $transport);

        $station = $client->stations()->create([
            'name' => 'Chillwave',
            'stream_url' => 'https://stream.example/live',
        ]);

        self::assertSame('station-uuid', $station['uuid']);
        $request = $transport->requests[0]['request'];
        self::assertSame('POST', $request->getMethod());
        self::assertSame('https://radio.example/api/v1/stations', (string) $request->getUri());
        self::assertSame('Bearer radio_live_secret', $request->getHeaderLine('Authorization'));
        self::assertSame([
            'name' => 'Chillwave',
            'stream_url' => 'https://stream.example/live',
        ], json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function test_it_uses_the_configured_now_playing_host_without_an_api_key(): void
    {
        $transport = new FakeTransport([
            new Response(200, [], json_encode(['song' => 'Night Transit', 'metadataFound' => true], JSON_THROW_ON_ERROR)),
        ]);
        $client = Client::forPublicNowPlaying('https://nowplaying.example', $transport);

        $nowPlaying = $client->nowPlaying('station uuid');

        self::assertSame('Night Transit', $nowPlaying['song']);
        self::assertSame('https://nowplaying.example/stations/station%20uuid/nowplaying', (string) $transport->requests[0]['request']->getUri());
        self::assertFalse($transport->requests[0]['request']->hasHeader('Authorization'));
    }

    public function test_it_creates_and_disables_a_direct_stream_router_route(): void
    {
        $transport = new FakeTransport([
            new Response(201, [], json_encode([
                'data' => [
                    'uuid' => 'route-uuid',
                    'station_uuid' => null,
                    'target_url' => 'https://cdn.example/live.mp3',
                    'slug' => 'direct-live',
                ],
            ], JSON_THROW_ON_ERROR)),
            new Response(204),
        ]);
        $client = new Client('https://radio.example', 'radio_live_secret', httpClient: $transport);

        $route = $client->streamRouter()->create([
            'target_url' => 'https://cdn.example/live.mp3',
            'slug' => 'direct-live',
        ]);
        $client->streamRouter()->delete($route['uuid']);

        self::assertNull($route['station_uuid']);
        self::assertSame('https://cdn.example/live.mp3', $route['target_url']);
        self::assertSame('https://radio.example/api/v1/stream-links', (string) $transport->requests[0]['request']->getUri());
        self::assertSame('DELETE', $transport->requests[1]['request']->getMethod());
        self::assertSame('https://radio.example/api/v1/stream-links/route-uuid', (string) $transport->requests[1]['request']->getUri());
    }

    public function test_it_exposes_api_errors_with_their_status_and_code(): void
    {
        $transport = new FakeTransport([
            new Response(422, [], json_encode([
                'error' => [
                    'code' => 'validation_error',
                    'message' => 'The submitted data is invalid.',
                ],
            ], JSON_THROW_ON_ERROR)),
        ]);
        $client = new Client('https://radio.example', 'radio_live_secret', httpClient: $transport);

        try {
            $client->stations()->create(['name' => 'Missing URL']);
            self::fail('Expected an API exception.');
        } catch (ApiException $exception) {
            self::assertSame(422, $exception->status);
            self::assertSame('validation_error', $exception->errorCode);
            self::assertSame('The submitted data is invalid.', $exception->getMessage());
        }
    }
}
