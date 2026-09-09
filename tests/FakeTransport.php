<?php

declare(strict_types=1);

namespace RadioApi\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class FakeTransport implements ClientInterface
{
    /** @var list<array{request: RequestInterface}> */
    public array $requests = [];

    private ClientInterface $client;

    /** @param list<ResponseInterface> $responses */
    public function __construct(array $responses)
    {
        $history = Middleware::history($this->requests);
        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push($history);
        $this->client = new Client(['handler' => $stack]);
    }

    public function send(RequestInterface $request, array $options = []): ResponseInterface { return $this->client->send($request, $options); }
    public function sendAsync(RequestInterface $request, array $options = []): PromiseInterface { return $this->client->sendAsync($request, $options); }
    public function request(string $method, $uri, array $options = []): ResponseInterface { return $this->client->request($method, $uri, $options); }
    public function requestAsync(string $method, $uri, array $options = []): PromiseInterface { return $this->client->requestAsync($method, $uri, $options); }
    public function getConfig(?string $option = null) { return $this->client->getConfig($option); }
}
