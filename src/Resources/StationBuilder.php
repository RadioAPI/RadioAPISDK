<?php

declare(strict_types=1);

namespace RadioApi\Resources;

final class StationBuilder
{
    /** @var array<string, mixed> */
    private array $attributes = [];

    public function __construct(private readonly Stations $stations) {}

    public function name(string $name): self
    {
        $this->attributes['name'] = $name;

        return $this;
    }

    public function streamUrl(string $streamUrl): self
    {
        $this->attributes['stream_url'] = $streamUrl;

        return $this;
    }

    /**
     * @param  'jcplayer'|'azuracast'|'radioking'|'live365'  $provider
     */
    public function streamMetadataProvider(string $provider): self
    {
        $this->attributes['stream_metadata_provider'] = $provider;

        return $this;
    }

    public function streamMetadataUrl(string $url): self
    {
        $this->metadataSettings()['url'] = $url;

        return $this;
    }

    public function streamMetadataGenre(string $genre): self
    {
        $this->metadataSettings()['genre'] = $genre;

        return $this;
    }

    public function language(string $language): self
    {
        $this->metadataSettings()['language'] = $language;

        return $this;
    }

    public function musicProvider(string $musicProvider): self
    {
        $this->attributes['music_provider'] = $musicProvider;

        return $this;
    }

    /**
     * @param  'public'|'private'  $access
     */
    public function nowPlayingAccess(string $access): self
    {
        $this->attributes['nowplaying_access'] = $access;

        return $this;
    }

    public function nowPlayingHistoryEnabled(bool $enabled = true): self
    {
        $this->attributes['nowplaying_history_enabled'] = $enabled;

        return $this;
    }

    public function slug(string $slug): self
    {
        $this->attributes['slug'] = $slug;

        return $this;
    }

    /** @return array<string, mixed> */
    public function attributes(): array
    {
        return $this->attributes;
    }

    /** @return array<string, mixed> */
    public function create(): array
    {
        return $this->stations->create($this->attributes);
    }

    /** @return array<string, mixed> */
    public function update(string $stationUuid): array
    {
        return $this->stations->update($stationUuid, $this->attributes);
    }

    /** @return array<string, mixed> */
    private function &metadataSettings(): array
    {
        if (! is_array($this->attributes['stream_metadata_settings'] ?? null)) {
            $this->attributes['stream_metadata_settings'] = [];
        }

        return $this->attributes['stream_metadata_settings'];
    }
}
