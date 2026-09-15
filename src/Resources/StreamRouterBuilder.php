<?php

declare(strict_types=1);

namespace RadioApi\Resources;

final class StreamRouterBuilder
{
    /** @var array<string, mixed> */
    private array $attributes = [];

    public function __construct(private readonly StreamRouter $streamRouter) {}

    public function slug(string $slug): self
    {
        $this->attributes['slug'] = $slug;

        return $this;
    }

    public function stationUuid(string $stationUuid): self
    {
        $this->attributes['station_uuid'] = $stationUuid;
        unset($this->attributes['target_url']);

        return $this;
    }

    public function targetUrl(string $targetUrl): self
    {
        $this->attributes['target_url'] = $targetUrl;
        unset($this->attributes['station_uuid']);

        return $this;
    }

    /**
     * @param  302|307  $statusCode
     */
    public function redirectStatusCode(int $statusCode): self
    {
        $this->attributes['redirect_status_code'] = $statusCode;

        return $this;
    }

    public function isActive(bool $active = true): self
    {
        $this->attributes['is_active'] = $active;

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
        return $this->streamRouter->create($this->attributes);
    }

    /** @return array<string, mixed> */
    public function update(string $routeUuid): array
    {
        return $this->streamRouter->update($routeUuid, $this->attributes);
    }
}
