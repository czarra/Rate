<?php

namespace App\Tests\Service;

use App\Exception\RateException;
use App\Service\Rate;
use App\Service\RateInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\ResponseInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class RateTest extends TestCase
{
    /** @var HttpClientInterface&MockObject */
    private $client;

    private RateInterface $rate;

    private const KEY = 'KEY';

    private const URL = 'URL';

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = $this->createMock(HttpClientInterface::class);

        $this->rate = new Rate($this->client, self::KEY, self::URL);
    }

    public function testGetRatesIsOK(): void
    {
        $data = ['USD' => 1.2];
        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(Response::HTTP_OK);

        $response->expects($this->once())
            ->method('toArray')
            ->willReturn(['rates' => $data]);

        $this->client->expects($this->once())->method('request')
            ->with('GET', self::URL . self::KEY)
            ->willReturn($response);

        $rates = $this->rate->getRates();

        $this->assertIsArray($rates);
        $this->assertSame($rates, $data);
    }

    public function testGetRatesIncorrectResponse(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(Response::HTTP_INTERNAL_SERVER_ERROR);

        $response->expects($this->never())->method('toArray');

        $this->client->expects($this->once())->method('request')
            ->with('GET', self::URL . self::KEY)
            ->willReturn($response);

        $this->expectException(RateException::class);
        $this->expectExceptionMessage('Exchangeratesapi not respond correctly');
        $this->rate->getRates();
    }

    public function testGetRatesIncorrectResponseToArray(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(Response::HTTP_OK);

        $response->expects($this->once())->method('toArray')->willReturn([]);

        $this->client->expects($this->once())->method('request')
            ->with('GET', self::URL . self::KEY)
            ->willReturn($response);

        $this->expectException(RateException::class);
        $this->expectExceptionMessage('Exchangeratesapi wrong respond');
        $this->rate->getRates();
    }
}
