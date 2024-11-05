<?php

declare(strict_types=1);

namespace Artemeon\Ollama;

use Artemeon\Ollama\Enum\Format;
use GuzzleHttp\Client as Http;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;

readonly class Client
{
    final public static function create(string $baseUrl, ?string $model = null): static
    {
        return ClientFactory::create($baseUrl, static::class, $model);
    }

    /**
     * @internal
     */
    final public static function fake(?array $queue = null, ?string $model = null, array &$container = []): self
    {
        return ClientFactory::fake($queue, $model, $container);
    }

    public function __construct(
        private Http $http,
        private ?string $model = null,
    ) {
    }

    public function generate(
        string $prompt,
        ?Format $format = null,
        ?array $options = null,
        ?string $system = null,
        ?string $model = null,
    ): ResponseInterface {
        return $this->request('/api/generate', $prompt, array_filter([
            'format' => $format->value ?? null,
            'options' => $options,
            'system' => $system,
        ]), $model);
    }

    public function getTags(): ResponseInterface
    {
        return $this->http->get('/api/tags');
    }

    public function showModel(string $name, ?bool $verbose = null): ResponseInterface
    {
        return $this->http->post('/api/show', ['json' => array_filter(['name' => $name, 'verbose' => $verbose])]);
    }

    private function request(string $endpoint, string $prompt, array $data, ?string $model = null): ResponseInterface
    {
        $model ??= $this->model;

        if ($model === null) {
            throw new InvalidArgumentException('Model is required');
        }

        return $this->http->post($endpoint, ['json' => [...$data, 'prompt' => $prompt, 'model' => $model, 'stream' => false]]);
    }
}
