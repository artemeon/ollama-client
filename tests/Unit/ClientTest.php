<?php

declare(strict_types=1);

use Artemeon\Ollama\Client;
use Artemeon\Ollama\ClientFactory;
use Artemeon\Ollama\Enum\Format;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;

covers(Client::class, ClientFactory::class);

it('should create a configured client', function (string $baseUrl): void {
    $client = Client::create($baseUrl);

    $config = invade(invade($client)->http)->config;

    expect($client)->toBeInstanceOf(Client::class)
        ->and($config['base_uri'])->toEqual(new Uri($baseUrl));
})->with(['http://localhost', 'http://127.0.0.1:12345']);

it('should throw an exception when an invalid base url is provided', function (): void {
    Client::create('foo');
})->throws(InvalidArgumentException::class, 'Invalid Base URL');

it('should create a fake client', function (): void {
    $client = Client::fake();

    expect($client)->toBeInstanceOf(Client::class);
});

describe('Generate Endpoint', function (): void {
    it('should send the request', function (string $prompt, string $model): void {
        $container = [];
        $client = Client::fake(container: $container);

        $client->generate($prompt, model: $model);

        /** @var Request $request */
        $request = $container[0]['request'];

        expect($container)->toHaveCount(1)
            ->and($request->getUri()->getPath())->toBe('/api/generate')
            ->and($request->getBody()->getContents())
            ->json()
            ->prompt->toBe($prompt)
            ->model->toBe($model);
    })->with([['Hello, world!', 'llama3:latest']]);

    it('should generate a response', function (): void {
        $client = Client::fake([new Response(201)]);

        $response = $client->generate('Hello, world!', model: 'llama3:latest');

        expect($response->getStatusCode())->toBe(201);
    });

    it('should generate a fake default response', function (): void {
        $client = Client::fake();

        $response = $client->generate('Hello, world!', model: 'llama3:latest');

        expect($response->getStatusCode())->toBe(200);
    });

    it('should throw an exception when model is not provided', function (): void {
        $client = Client::fake();

        $client->generate('Hello, world!');
    })->throws(InvalidArgumentException::class, 'Model is required');

    it('should create fake client with default url', function (): void {
        $client = Client::fake();

        $config = invade(invade($client)->http)->config;

        expect($client)->toBeInstanceOf(Client::class)
            ->and($config['base_uri'])->toEqual(new Uri('http://localhost'));
    });

    it('can overwrite the default model', function (): void {
        $container = [];
        $client = Client::fake(model: 'llama3:latest', container: $container);

        $client->generate('Hello, world!', model: 'llama3.2:latest');

        /** @var Request $request */
        $request = $container[0]['request'];

        expect($container)->toHaveCount(1)
            ->and($request->getBody()->getContents())
            ->json()
            ->toHaveCount(3)
            ->model->toBe('llama3.2:latest');
    });

    it('should set the format', function (): void {
        $container = [];
        $client = Client::fake(model: 'llama3:latest', container: $container);

        $client->generate('Hello, world!', format: Format::JSON);

        /** @var Request $request */
        $request = $container[0]['request'];

        expect($container)->toHaveCount(1)
            ->and($request->getBody()->getContents())
            ->json()
            ->toHaveCount(4)
            ->format->toBe(Format::JSON->value);
    });

    it('should set the system prompt', function (): void {
        $container = [];
        $client = Client::fake(model: 'llama3:latest', container: $container);

        $client->generate('Hello, world!', system: 'Lorem ipsum');

        /** @var Request $request */
        $request = $container[0]['request'];

        expect($container)->toHaveCount(1)
            ->and($request->getBody()->getContents())
            ->json()
            ->toHaveCount(4)
            ->system->toBe('Lorem ipsum');
    });

    it('should set options', function (): void {
        $container = [];
        $client = Client::fake(model: 'llama3:latest', container: $container);

        $client->generate('Hello, world!', options: ['temperature' => 0.5]);

        /** @var Request $request */
        $request = $container[0]['request'];

        expect($container)->toHaveCount(1)
            ->and($request->getBody()->getContents())
            ->json()
            ->toHaveCount(4)
            ->options->toBe(['temperature' => 0.5]);
    });
});

describe('Tags Endpoint', function (): void {
    it('should request tags', function (): void {
        $container = [];
        $client = Client::fake(container: $container);

        $client->getTags();

        expect($container)->toHaveCount(1);
    });
});

describe('Show Model Endpoint', function (): void {
    it('should show model information', function (): void {
        $container = [];
        $client = Client::fake(container: $container);

        $client->showModel('llama3:latest');

        /** @var Request $request */
        $request = $container[0]['request'];

        expect($container)->toHaveCount(1)
            ->and($request->getBody()->getContents())
            ->json()
            ->toHaveCount(1)
            ->name->toBe('llama3:latest');
    });

    it('should show verbose model information', function (): void {
        $container = [];
        $client = Client::fake(container: $container);

        $client->showModel('llama3:latest', true);

        /** @var Request $request */
        $request = $container[0]['request'];

        expect($container)->toHaveCount(1)
            ->and($request->getBody()->getContents())
            ->json()
            ->toHaveCount(2)
            ->verbose->toBe(true);
    });
});
