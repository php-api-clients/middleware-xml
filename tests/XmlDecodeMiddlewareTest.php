<?php declare(strict_types=1);

namespace ApiClients\Tests\Middleware\Xml;

use ApiClients\Middleware\Xml\Options;
use ApiClients\Middleware\Xml\XmlDecodeMiddleware;
use ApiClients\Middleware\Xml\XmlStream;
use ApiClients\Tools\TestUtilities\TestCase;
use ApiClients\Tools\Xml\XmlDecodeService;
use React\EventLoop\Factory;
use RingCentral\Psr7\Response;
use function Clue\React\Block\await;

class XmlDecodeMiddlewareTest extends TestCase
{
    public function provideValidJsonContentTypes()
    {
        yield ['text/xml'];
        yield ['text/xml; charset=utf-8'];
    }

    /**
     * @dataProvider provideValidJsonContentTypes
     */
    public function testPost(string $contentType)
    {
        $loop = Factory::create();
        $middleware = new XmlDecodeMiddleware(new XmlDecodeService($loop));
        $response = new Response(200, ['Content-Type' => $contentType], Constant::XML);

        $body = await(
            $middleware->post($response, 'abc'),
            $loop
        )->getBody();

        self::assertInstanceOf(XmlStream::class, $body);

        self::assertSame(
            Constant::TREE,
            $body->getParsedContents()
        );
    }

    public function testPostNoContentType()
    {
        $loop = Factory::create();
        $middleware = new XmlDecodeMiddleware(new XmlDecodeService($loop));
        $response = new Response(200, [], '[]');

        self::assertSame(
            $response,
            await(
                $middleware->post($response, 'abc'),
                $loop
            )
        );
    }

    public function testPostNoContentTypeCheck()
    {
        $loop = Factory::create();
        $middleware = new XmlDecodeMiddleware(new XmlDecodeService($loop));
        $response = new Response(200, [], Constant::XML);

        $body = await(
            $middleware->post(
                $response,
                'abc',
                [
                    XmlDecodeMiddleware::class => [
                        Options::NO_CONTENT_TYPE_CHECK => true,
                    ],
                ]
            ),
            $loop
        )->getBody();

        self::assertInstanceOf(XmlStream::class, $body);

        self::assertSame(
            Constant::TREE,
            $body->getParsedContents()
        );
    }

    public function testPostCustomTYpe()
    {
        $loop = Factory::create();
        $middleware = new XmlDecodeMiddleware(new XmlDecodeService($loop));
        $response = new Response(200, ['Content-Type' => 'custom/type'], Constant::XML);

        $body = await(
            $middleware->post(
                $response,
                'abc',
                [
                    XmlDecodeMiddleware::class => [
                        Options::CONTENT_TYPE => 'custom/type',
                    ],
                ]
            ),
            $loop
        )->getBody();

        self::assertInstanceOf(XmlStream::class, $body);

        self::assertSame(
            Constant::TREE,
            $body->getParsedContents()
        );
    }

    public function testPostNoJson()
    {
        $loop = Factory::create();
        $middleware = new XmlDecodeMiddleware(new XmlDecodeService($loop));
        $response = new Response(200, []);

        self::assertSame(
            $response,
            await(
                $middleware->post($response, 'abc'),
                $loop
            )
        );
    }

    public function testPostEmpty()
    {
        $loop = Factory::create();
        $middleware = new XmlDecodeMiddleware(new XmlDecodeService($loop));
        $response = new Response(200, [], '');

        self::assertSame(
            '',
            (string)await(
                $middleware->post($response, 'abc'),
                $loop
            )->getBody()
        );
    }
}
