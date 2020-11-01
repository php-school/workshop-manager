<?php

declare(strict_types=1);

namespace PhpSchool\WorkshopManagerTest\GitHubApi;

use GuzzleHttp\Psr7\Response;
use PhpSchool\WorkshopManager\GitHubApi\Client;
use PhpSchool\WorkshopManager\GitHubApi\Exception;
use PHPUnit\Framework\TestCase;
use Http\Mock\Client as MockClient;
use GuzzleHttp\Psr7;

class ClientTest extends TestCase
{
    public function testExceptionIsThrownIfApiIsExceeded(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('You have reached GitHub hourly limit! Actual limit is: 100');

        $response = new Response(420, ['X-RateLimit-Remaining' => '0', 'X-RateLimit-Limit' => 100]);

        $client = new MockClient();
        $client->addResponse($response);

        $ghClient = new Client($client);
        $ghClient->tags('php-school', 'workshop-manager');
    }

    public function testExceptionIsThrownIf400StatusReturnedWithMessage(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Some error (Bad Request)');

        $response = (new Response(400))
            ->withHeader('Content-Type', 'application/json')
            ->withBody(Psr7\Utils::streamFor('{"message": "Some error"}'));

        $client = new MockClient();
        $client->addResponse($response);

        $ghClient = new Client($client);
        $ghClient->tags('php-school', 'workshop-manager');
    }

    public function validationFailedProvider(): array
    {
        return [
            'missing' => [
                'error' => 'Validation Failed: The a-field a-value does not exist, for resource "a-resource"',
                'response' => json_encode([
                    'message' => 'Some error',
                    'errors' => [
                        [
                            'code' => 'missing',
                            'field' => 'a-field',
                            'value' => 'a-value',
                            'resource' => 'a-resource'
                        ]
                    ]
                ])
            ],
            'missing-field' => [
                'error' => 'Field "a-field" is missing, for resource "a-resource"',
                'response' => json_encode([
                    'message' => 'Some error',
                    'errors' => [
                        [
                            'code' => 'missing_field',
                            'field' => 'a-field',
                            'value' => 'a-value',
                            'resource' => 'a-resource'
                        ]
                    ]
                ])
            ],
            'invalid' => [
                'error' => 'Validation Failed: Field "a-field" is invalid, for resource "a-resource": some error',
                'response' => json_encode([
                    'message' => 'Some error',
                    'errors' => [
                        [
                            'code' => 'invalid',
                            'field' => 'a-field',
                            'value' => 'a-value',
                            'resource' => 'a-resource',
                            'message' => 'some error'
                        ]
                    ]
                ])
            ],
            'invalid-no-message' => [
                'error' => 'Validation Failed: Field "a-field" is invalid, for resource "a-resource"',
                'response' => json_encode([
                    'message' => 'Some error',
                    'errors' => [
                        [
                            'code' => 'invalid',
                            'field' => 'a-field',
                            'value' => 'a-value',
                            'resource' => 'a-resource',
                        ]
                    ]
                ])
            ],
            'already-exists' => [
                'error' => 'Validation Failed: Field "a-field" already exists, for resource "a-resource"',
                'response' => json_encode([
                    'message' => 'Some error',
                    'errors' => [
                        [
                            'code' => 'already_exists',
                            'field' => 'a-field',
                            'value' => 'a-value',
                            'resource' => 'a-resource',
                        ]
                    ]
                ])
            ],
            'default' => [
                'error' => 'Validation Failed: Other message',
                'response' => json_encode([
                    'message' => 'Some error',
                    'errors' => [
                        [
                            'code' => 'other-code',
                            'message' => 'Other message',
                        ]
                    ]
                ])
            ],
            'multiple-messages' => [
                'error' => 'Validation Failed: Field "a-field" is invalid, for resource "a-resource", Field "b-field" '
                         . 'is invalid, for resource "b-resource"',
                'response' => json_encode([
                    'message' => 'Some error',
                    'errors' => [
                        [
                            'code' => 'invalid',
                            'field' => 'a-field',
                            'value' => 'a-value',
                            'resource' => 'a-resource',
                        ],
                        [
                            'code' => 'invalid',
                            'field' => 'b-field',
                            'value' => 'b-value',
                            'resource' => 'b-resource',
                        ]
                    ]
                ])
            ],
        ];
    }

    /**
     * @dataProvider validationFailedProvider
     */
    public function testExceptionIsThrownIfValidationFailed(string $error, string $response): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage($error);

        $response = (new Response(422))
            ->withHeader('Content-Type', 'application/json')
            ->withBody(Psr7\Utils::streamFor($response));

        $client = new MockClient();
        $client->addResponse($response);

        $ghClient = new Client($client);
        $ghClient->tags('php-school', 'workshop-manager');
    }

    public function test500WithErrors(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Error 1, Error 2, Error 3');

        $response = (new Response(502))
            ->withHeader('Content-Type', 'application/json')
            ->withBody(Psr7\Utils::streamFor(
                json_encode(
                    ['errors' => [['message' => 'Error 1'], ['message' => 'Error 2'], ['message' => 'Error 3']]]
                )
            ));

        $client = new MockClient();
        $client->addResponse($response);

        $ghClient = new Client($client);
        $ghClient->tags('php-school', 'workshop-manager');
    }

    public function testDefaultError(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Some error');

        $response = (new Response(502))
            ->withHeader('Content-Type', 'application/json')
            ->withBody(Psr7\Utils::streamFor(
                json_encode(
                    ['message' => 'Some error']
                )
            ));

        $client = new MockClient();
        $client->addResponse($response);

        $ghClient = new Client($client);
        $ghClient->tags('php-school', 'workshop-manager');
    }

    public function testDefaultErrorNoMessageKey(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Some error');

        $response = (new Response(502))
            ->withHeader('Content-Type', 'application/json')
            ->withBody(Psr7\Utils::streamFor('Some error'));

        $client = new MockClient();
        $client->addResponse($response);

        $ghClient = new Client($client);
        $ghClient->tags('php-school', 'workshop-manager');
    }

    public function testTags(): void
    {
        $response = (new Response(200))
            ->withHeader('Content-Type', 'application/json')
            ->withBody(Psr7\Utils::streamFor('{}'));

        $client = new MockClient();
        $client->addResponse($response);

        $ghClient = new Client($client);
        $ghClient->tags('php-school', 'workshop-manager');

        $request = $client->getLastRequest();

        $this->assertSame(
            'https://api.github.com/repos/php-school/workshop-manager/git/refs/tags',
            $request->getUri()->__toString()
        );
        $this->assertSame(
            'php-school/workshop-manager (https://www.phpschool.io/)',
            $request->getHeader('User-Agent')[0]
        );
        $this->assertSame(
            'application/vnd.github.3+json',
            $request->getHeader('Accept')[0]
        );
    }

    public function testZipballArchiveWithoutRef(): void
    {
        $response = (new Response(200))
            ->withBody(Psr7\Utils::streamFor('filecontent'));

        $client = new MockClient();
        $client->addResponse($response);

        $ghClient = new Client($client);
        $ghClient->archive('php-school', 'workshop-manager', 'zipball');

        $request = $client->getLastRequest();

        $this->assertSame(
            'https://api.github.com/repos/php-school/workshop-manager/zipball',
            $request->getUri()->__toString()
        );
        $this->assertSame(
            'php-school/workshop-manager (https://www.phpschool.io/)',
            $request->getHeader('User-Agent')[0]
        );
        $this->assertSame(
            'application/vnd.github.3+json',
            $request->getHeader('Accept')[0]
        );
    }

    public function testTarballArchiveWithoutRef(): void
    {
        $response = (new Response(200))
            ->withBody(Psr7\Utils::streamFor('filecontent'));

        $client = new MockClient();
        $client->addResponse($response);

        $ghClient = new Client($client);
        $ghClient->archive('php-school', 'workshop-manager', 'tarball');

        $request = $client->getLastRequest();

        $this->assertSame(
            'https://api.github.com/repos/php-school/workshop-manager/tarball',
            $request->getUri()->__toString()
        );
        $this->assertSame(
            'php-school/workshop-manager (https://www.phpschool.io/)',
            $request->getHeader('User-Agent')[0]
        );
        $this->assertSame(
            'application/vnd.github.3+json',
            $request->getHeader('Accept')[0]
        );
    }

    public function testArchiveWithWrongFormatDefaultsToTarball(): void
    {
        $response = (new Response(200))
            ->withBody(Psr7\Utils::streamFor('filecontent'));

        $client = new MockClient();
        $client->addResponse($response);

        $ghClient = new Client($client);
        $ghClient->archive('php-school', 'workshop-manager', 'bzip');

        $request = $client->getLastRequest();

        $this->assertSame(
            'https://api.github.com/repos/php-school/workshop-manager/tarball',
            $request->getUri()->__toString()
        );
        $this->assertSame(
            'php-school/workshop-manager (https://www.phpschool.io/)',
            $request->getHeader('User-Agent')[0]
        );
        $this->assertSame(
            'application/vnd.github.3+json',
            $request->getHeader('Accept')[0]
        );
    }

    public function testArchiveWithRef(): void
    {
        $response = (new Response(200))
            ->withBody(Psr7\Utils::streamFor('filecontent'));

        $client = new MockClient();
        $client->addResponse($response);

        $ghClient = new Client($client);
        $ghClient->archive('php-school', 'workshop-manager', 'zipball', '5665');

        $request = $client->getLastRequest();

        $this->assertSame(
            'https://api.github.com/repos/php-school/workshop-manager/zipball/5665',
            $request->getUri()->__toString()
        );
        $this->assertSame(
            'php-school/workshop-manager (https://www.phpschool.io/)',
            $request->getHeader('User-Agent')[0]
        );
        $this->assertSame(
            'application/vnd.github.3+json',
            $request->getHeader('Accept')[0]
        );
    }
}
