<?php

declare(strict_types=1);

namespace RadioApi\Resources;

final class StationQuery
{
    /** @var array<string, bool|int|string> */
    private array $query = [];

    public function __construct(private readonly Stations $stations) {}

    public function search(string $search): self
    {
        $this->query['search'] = $search;

        return $this;
    }

    public function provider(string $provider): self
    {
        $this->query['provider'] = $provider;

        return $this;
    }

    /**
     * @param  'active'|'inactive'  $status
     */
    public function status(string $status): self
    {
        $this->query['status'] = $status;

        return $this;
    }

    /**
     * @param  'created_at'|'name'  $field
     */
    public function sortBy(string $field): self
    {
        $this->query['sort'] = $field;

        return $this;
    }

    /**
     * @param  'asc'|'desc'  $direction
     */
    public function direction(string $direction): self
    {
        $this->query['direction'] = $direction;

        return $this;
    }

    public function ascending(): self
    {
        return $this->direction('asc');
    }

    public function descending(): self
    {
        return $this->direction('desc');
    }

    public function perPage(int $perPage): self
    {
        $this->query['per_page'] = $perPage;

        return $this;
    }

    /** @return array{data: list<array<string, mixed>>, links: array<string, mixed>, meta: array<string, mixed>} */
    public function get(): array
    {
        return $this->stations->list($this->query);
    }

    /** @return array<string, bool|int|string> */
    public function parameters(): array
    {
        return $this->query;
    }
}
