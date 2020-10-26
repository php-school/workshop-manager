<?php

declare(strict_types=1);

namespace PhpSchool\WorkshopManager\GitHubApi;

use Github\Exception\RuntimeException;
use Http\Client\Common\HttpMethodsClient;
use Http\Client\Common\PluginClientFactory;
use Http\Client\HttpClient;
use Http\Discovery\HttpClientDiscovery;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Client\Common\Plugin;
use Psr\Http\Message\RequestFactoryInterface;

class Client
{
    /**
     * @var HttpClient
     */
    private $httpClient;

    /**
     * @var RequestFactoryInterface
     */
    private $requestFactory;

    public function __construct(
        HttpClient $httpClient = null,
        RequestFactoryInterface $requestFactory = null
    ) {
        $this->httpClient = $httpClient ?: HttpClientDiscovery::find();
        $this->requestFactory = $requestFactory ?: Psr17FactoryDiscovery::findRequestFactory();
    }

    public function getClient(): HttpMethodsClient
    {
        $plugins = [
            new Plugin\AddHostPlugin(Psr17FactoryDiscovery::findUriFactory()->createUri('https://api.github.com')),
            new Plugin\RedirectPlugin(),
            new Plugin\HeaderAppendPlugin([
                'User-Agent' => 'php-school/workshop-manager (https://www.phpschool.io/)',
                'Accept' => 'application/vnd.github.3+json'
            ]),
            new GitHubExceptionThrower()
        ];

        return new HttpMethodsClient(
            (new PluginClientFactory())->createClient($this->httpClient, $plugins),
            $this->requestFactory
        );
    }

    /**
     * @return array<array>
     */
    public function tags(string $username, string $repository): array
    {
        $response = $this->getClient()
            ->get('/repos/' . rawurlencode($username).'/'.rawurlencode($repository).'/git/refs/tags');

        /** @var array<array> $response */
        $response = Response::parse($response);
        return $response;
    }

    public function archive(string $username, string $repository, string $format, string $ref = null): string
    {
        if (!in_array($format, ['tarball', 'zipball'], true)) {
            $format = 'tarball';
        }

        $endpoint = sprintf(
            '/repos/%s/%s/%s%s',
            rawurlencode($username),
            rawurlencode($repository),
            rawurlencode($format),
            null !== $ref ? '/' . rawurlencode($ref) : ''
        );

        /** @var string $response */
        $response = Response::parse($this->getClient()->get($endpoint));
        return $response;
    }
}
