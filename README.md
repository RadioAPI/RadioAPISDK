# RadioAPI PHP SDK

A small PHP client for RadioAPI. It covers station management, public and private Now Playing requests, and Stream Router routes.

## Install

```bash
composer require radioapi/php
```

The package requires PHP 8.2+ and `ext-json`. It uses Guzzle's `ClientInterface`, so you can inject your own configured HTTP client.

To publish it, release the contents of this `package/` directory as the
`radioapi/php` repository, tag a version such as `v1.0.0`, and submit that
repository to Packagist. Composer will then resolve the command above.

## Start here

Create a client with an API key for the Management API. Pass the root URL of your RadioAPI app, without `/api/v1`.

```php
use RadioApi\Client;

$radio = new Client(
    baseUrl: 'https://radioapi.example.com',
    apiKey: 'radio_live_YOUR_API_KEY',
);
```

The base URL comes first so the simplest setup is also clear when using positional arguments:

```php
$radio = Client::make('https://radioapi.example.com', 'radio_live_YOUR_API_KEY');
```

Inject a configured Guzzle client only when you need custom middleware, retries, timeouts, or a test client:

```php
$radio = Client::make('https://radioapi.example.com', 'radio_live_YOUR_API_KEY', $guzzle);
```

For public Now Playing, use a client without an API key and the same API base URL:

```php
$radio = new Client(
    apiKey: null,
    baseUrl: 'https://radioapi.example.com',
);
```

## Stations

```php
$station = $radio->stations()->create([
    'name' => 'Chillwave Radio',
    'stream_url' => 'https://stream.example.com/live.mp3',
    'stream_metadata_provider' => 'azuracast',
    'stream_metadata_settings' => [
        'url' => 'https://station.example.com/api/nowplaying/chillwave',
        'genre' => 'Electronic',
    ],
    'music_provider' => 'auto',
    'nowplaying_access' => 'public',
    'nowplaying_history_enabled' => true,
]);

echo $station['uuid'];
```

The supported stream metadata providers are `jcplayer` (generic/ICY), `azuracast`, `radioking`, and `live365`. AzuraCast, RadioKing, and Live365 require `stream_metadata_settings.url`.

```php
$page = $radio->stations()->list([
    'status' => 'active',
    'per_page' => 25,
]);

$station = $radio->stations()->find('STATION_UUID');

$station = $radio->stations()->update('STATION_UUID', [
    'nowplaying_access' => 'private',
]);

// Disables the station. It does not hard-delete it.
$radio->stations()->delete('STATION_UUID');
```

`list()` returns the API pagination object with `data`, `links`, and `meta`. `create()`, `find()`, and `update()` return the unwrapped resource from `data`.

## Now Playing

Use the same authenticated client for a private station. Public stations do not need an API key:

```php
$nowPlaying = $radio->nowPlaying('STATION_UUID');

echo $nowPlaying['artist'].' — '.$nowPlaying['song'];
```

```php
use RadioApi\Client;

$publicRadio = Client::forPublicNowPlaying(
    'https://nowplaying.radioapi.example.com',
);

$nowPlaying = $publicRadio->nowPlaying('STATION_UUID');
```

Now Playing returns a top-level metadata object. The upstream `stream` field is never exposed. The payload can include `name`, `bitrate`, `format`, `artist`, `song`, `album`, `genre`, `artwork`, `year`, `duration`, `elapsed`, `remaining`, `time`, `lyrics`, `explicit`, `songFound`, and `metadataFound`.

When Track History is enabled for the station and the provider has history available, the response also has a `history` array. Treat provider metadata as optional: an unavailable field is usually an empty string or zero value.

## Stream Router

Create a public route that follows a station’s current stream URL:

```php
$route = $radio->streamRouter()->create([
    'station_uuid' => 'STATION_UUID',
    'slug' => 'chillwave',
    'redirect_status_code' => 307,
]);

echo $route['url'];
```

Or route straight to an external stream without a station:

```php
$route = $radio->streamRouter()->create([
    'target_url' => 'https://cdn.example.com/chillwave.mp3',
    'slug' => 'chillwave-direct',
]);
```

```php
$routes = $radio->streamRouter()->list(['status' => 'active']);
$route = $radio->streamRouter()->find('ROUTE_UUID');

$route = $radio->streamRouter()->update('ROUTE_UUID', [
    'is_active' => false,
]);

// Disables the route. It does not hard-delete it.
$radio->streamRouter()->delete('ROUTE_UUID');
```

Use exactly one of `station_uuid` or `target_url` when creating or changing a route. Valid `redirect_status_code` values are `302` and `307`.

## Errors

All non-2xx responses throw `RadioApi\Exceptions\ApiException`. It carries the HTTP status, RadioAPI error code, response payload, and response headers.

```php
use RadioApi\Exceptions\ApiException;

try {
    $radio->stations()->create([
        'name' => 'Missing stream URL',
    ]);
} catch (ApiException $exception) {
    if ($exception->errorCode === 'validation_error') {
        $fields = $exception->response['error']['fields'] ?? [];
    }
}
```

Malformed or empty JSON responses throw `RadioApi\Exceptions\InvalidResponseException`. Network failures throw `RadioApi\Exceptions\TransportException` with the original Guzzle exception attached as the previous exception.

## Test the package

```bash
composer install
composer test
```

## License

MIT. See [LICENSE](LICENSE).
