<?php

declare(strict_types=1);

namespace PhpSchool\WorkshopManager\GitHubApi;

use Http\Client\Common\Plugin;
use Http\Promise\Promise;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class GitHubExceptionThrower implements Plugin
{
    public function handleRequest(RequestInterface $request, callable $next, callable $first): Promise
    {
        return $next($request)->then(function (ResponseInterface $response) use ($request) {
            if ($response->getStatusCode() < 400 || $response->getStatusCode() > 600) {
                return $response;
            }

            if ($response->hasHeader('X-RateLimit-Remaining')) {
                $remaining = (int) $response->getHeader('X-RateLimit-Remaining')[0];
                if (null !== $remaining && 1 > $remaining && 'rate_limit' !== substr($request->getRequestTarget(), 1, 10)) {
                    throw Exception::apiLimitExceeded(
                        (int) $response->getHeader('X-RateLimit-Limit')[0]
                    );
                }
            }

            $content = Response::parse($response);

            if (is_array($content) && isset($content['message'])) {
                if (400 === $response->getStatusCode()) {
                    throw new Exception(
                        sprintf('%s (%s)', $content['message'], $response->getReasonPhrase()),
                        400
                    );
                }

                if (422 === $response->getStatusCode() && isset($content['errors'])) {
                    throw $this->getValidationFailedException($content['errors']);
                }
            }

            if (502 === $response->getStatusCode()
                && is_array($content)
                && isset($content['errors'])
                && is_array($content['errors'])) {
                throw Exception::failed($content['errors']);
            }

            throw new Exception(
                is_array($content) && isset($content['message']) ? $content['message'] : $content,
                $response->getStatusCode()
            );
        });
    }

    /**
     * @param array<array> $errors
     */
    private function getValidationFailedException(array $errors): Exception
    {
        return Exception::validationFailed(array_filter(array_map(function (array $error) {
            switch($error['code']) {
                case 'missing':
                    return sprintf(
                        'The %s %s does not exist, for resource "%s"',
                        $error['field'],
                        $error['value'],
                        $error['resource']
                    );
                case 'missing_field':
                    return sprintf(
                        'Field "%s" is missing, for resource "%s"',
                        $error['field'],
                        $error['resource']
                    );
                case 'invalid':
                    return sprintf(
                        'Field "%s" is invalid, for resource "%s"%s',
                        $error['field'],
                        $error['resource'],
                        isset($error['message']) ? ': ' . $error['message'] : ''
                    );
                case 'already_exists':
                    return sprintf(
                        'Field "%s" already exists, for resource "%s"',
                        $error['field'],
                        $error['resource']
                    );
                default:
                    return $error['message'] ?? null;

            }
        }, $errors)));
    }
}
