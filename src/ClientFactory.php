<?php

declare(strict_types=1);

namespace Artemeon\Ollama;

use GuzzleHttp\Client as Http;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use InvalidArgumentException;

class ClientFactory
{
    /**
     * @template T of Client
     *
     * @param class-string<T> $clientClass
     *
     * @return T
     */
    public static function create(string $baseUrl, string $clientClass, ?string $model = null): mixed
    {
        if (parse_url($baseUrl, PHP_URL_HOST) === null) {
            throw new InvalidArgumentException('Invalid Base URL');
        }

        return new $clientClass(new Http(['base_uri' => $baseUrl]), $model);
    }

    public static function fake(?array $queue = null, ?string $model = null, array &$container = []): Client
    {
        $history = Middleware::history($container);
        $mock = new MockHandler($queue ?? [new Response(200)]);
        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push($history);

        return new Client(new Http([
            'handler' => $handlerStack,
            'base_uri' => 'http://localhost',
        ]), $model);
    }
}
