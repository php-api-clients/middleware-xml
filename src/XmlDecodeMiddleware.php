<?php declare(strict_types=1);

namespace ApiClients\Middleware\Xml;

use ApiClients\Foundation\Middleware\Annotation\ThirdLast;
use ApiClients\Foundation\Middleware\ErrorTrait;
use ApiClients\Foundation\Middleware\MiddlewareInterface;
use ApiClients\Foundation\Middleware\PreTrait;
use ApiClients\Tools\Json\JsonDecodeService;
use GuzzleHttp\Psr7\BufferStream;
use Psr\Http\Message\ResponseInterface;
use React\Promise\CancellablePromiseInterface;
use React\Stream\ReadableStreamInterface;
use function React\Promise\resolve;

class XmlDecodeMiddleware implements MiddlewareInterface
{
    use PreTrait;
    use ErrorTrait;

    /**
     * @param ResponseInterface $response
     * @param array $options
     * @return CancellablePromiseInterface
     *
     * @ThirdLast()
     */
    public function post(
        ResponseInterface $response,
        string $transactionId,
        array $options = []
    ): CancellablePromiseInterface {
        if ($response->getBody() instanceof ReadableStreamInterface) {
            return resolve($response);
        }

        if (!isset($options[self::class]) &&
            strpos($response->getHeaderLine('Content-Type'), 'application/json') !== 0) {
            return resolve($response);
        }

        if (isset($options[self::class][Options::CONTENT_TYPE]) &&
            $response->getHeaderLine('Content-Type') !== $options[self::class][Options::CONTENT_TYPE]) {
            return resolve($response);
        }

        $body = (string)$response->getBody();
        if ($body === '') {
            $stream = new BufferStream(0);
            $stream->write($body);
            return resolve($response->withBody($stream));
        }

        return $this->decodeService->decode($body)->then(function ($json) use ($response) {
            $body = new XmlStream($json);
            return resolve($response->withBody($body));
        });
    }
}
