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

    public function test_it_creates_a_station_with_the_fluent_builder(): void
    {
        $transport = new FakeTransport([
            new Response(201, [], json_encode(['data' => ['uuid' => 'station-uuid']], JSON_THROW_ON_ERROR)),
        ]);
        $client = new Client('https://radio.example', 'radio_live_secret', httpClient: $transport);

        $client->stations()->builder()
            ->name('Chillwave')
            ->streamUrl('https://stream.example/live')
            ->streamMetadataProvider('azuracast')
            ->streamMetadataUrl('https://station.example/nowplaying')
            ->streamMetadataGenre('Electronic')
            ->language('fr')
            ->musicProvider('auto')
            ->nowPlayingAccess('public')
            ->nowPlayingHistoryEnabled()
            ->slug('chillwave')
            ->create();

        self::assertSame([
            'name' => 'Chillwave',
            'stream_url' => 'https://stream.example/live',
            'stream_metadata_provider' => 'azuracast',
            'stream_metadata_settings' => [
                'url' => 'https://station.example/nowplaying',
                'genre' => 'Electronic',
                'language' => 'fr',
            ],
            'music_provider' => 'auto',
            'nowplaying_access' => 'public',
            'nowplaying_history_enabled' => true,
            'slug' => 'chillwave',
        ], json_decode((string) $transport->requests[0]['request']->getBody(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function test_it_lists_stations_with_a_fluent_query(): void
    {
        $transport = new FakeTransport([
            new Response(200, [], json_encode([
                'data' => [],
                'links' => [],
                'meta' => [],
            ], JSON_THROW_ON_ERROR)),
        ]);
        $client = new Client('https://radio.example', 'radio_live_secret', httpClient: $transport);

        $client->stations()->query()
            ->search('chill')
            ->provider('azuracast')
            ->status('active')
            ->sortBy('name')
            ->descending()
            ->perPage(25)
            ->get();

        self::assertSame(
            'https://radio.example/api/v1/stations?search=chill&provider=azuracast&status=active&sort=name&direction=desc&per_page=25',
            (string) $transport->requests[0]['request']->getUri(),
        );
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

    public function test_it_creates_a_stream_router_route_with_the_fluent_builder(): void
    {
        $transport = new FakeTransport([
            new Response(201, [], json_encode(['data' => ['uuid' => 'route-uuid']], JSON_THROW_ON_ERROR)),
        ]);
        $client = new Client('https://radio.example', 'radio_live_secret', httpClient: $transport);

        $client->streamRouter()->builder()
            ->stationUuid('station-uuid')
            ->slug('chillwave')
            ->redirectStatusCode(307)
            ->create();

        self::assertSame([
            'station_uuid' => 'station-uuid',
            'slug' => 'chillwave',
            'redirect_status_code' => 307,
        ], json_decode((string) $transport->requests[0]['request']->getBody(), true, 512, JSON_THROW_ON_ERROR));
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
