<?php

declare(strict_types=1);

namespace PhpSchool\WorkshopManager\GitHubApi;

use Psr\Http\Message\ResponseInterface;

class Response
{
    /**
     * @param ResponseInterface $response
     * @return array<mixed>|string
     */
    public static function parse(ResponseInterface $response)
    {
        $body = $response->getBody()->__toString();
        if (strpos($response->getHeaderLine('Content-Type'), 'application/json') === 0) {
            /** @var array<mixed>|string $content */
            $content = json_decode($body, true);
            if (JSON_ERROR_NONE === json_last_error()) {
                return $content;
            }
        }

        return $body;
    }
}
