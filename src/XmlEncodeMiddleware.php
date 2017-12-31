<?php declare(strict_types=1);

namespace ApiClients\Middleware\Xml;

use ApiClients\Foundation\Middleware\Annotation\Third;
use ApiClients\Foundation\Middleware\ErrorTrait;
use ApiClients\Foundation\Middleware\MiddlewareInterface;
use ApiClients\Foundation\Middleware\PostTrait;
use ApiClients\Foundation\Transport\ParsedContentsInterface;
use ApiClients\Tools\Xml\XmlEncodeService;
use Psr\Http\Message\RequestInterface;
use React\Promise\CancellablePromiseInterface;
use RingCentral\Psr7\BufferStream;
use function React\Promise\resolve;

class XmlEncodeMiddleware implements MiddlewareInterface
{
    use PostTrait;
    use ErrorTrait;

    /**
     * @var XmlEncodeService
     */
    private $xmlEncodeService;

    /**
     * @param XmlEncodeService $xmlEncodeService
     */
    public function __construct(XmlEncodeService $xmlEncodeService)
    {
        $this->xmlEncodeService = $xmlEncodeService;
    }

    /**
     * @param  RequestInterface            $request
     * @param  array                       $options
     * @return CancellablePromiseInterface
     *
     * @Third()
     */
    public function pre(
        RequestInterface $request,
        string $transactionId,
        array $options = []
    ): CancellablePromiseInterface {
        $body = $request->getBody();
        if (!($body instanceof ParsedContentsInterface)) {
            return resolve($request);
        }

        return $this->xmlEncodeService->encode($body->getParsedContents())->then(function (string $xml) use ($request) {
            $body = new BufferStream(strlen($xml));
            $body->write($xml);

            return resolve($request->withBody($body)->withAddedHeader('Content-Type', 'text/xml'));
        });
    }
}
