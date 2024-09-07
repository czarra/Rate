<?php

namespace App\Tests\Service;

use App\Exception\CountryByBinException;
use App\Service\CountryByBin;
use App\Service\CountryByBinInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\ResponseInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class CountryByBinTest extends TestCase
{
    /** @var HttpClientInterface&MockObject */
    private $client;

    private CountryByBinInterface $countryByBin;

    private const URL = 'URL';

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = $this->createMock(HttpClientInterface::class);

        $this->countryByBin = new CountryByBin($this->client, self::URL);
    }

    /**
     * @dataProvider dataBinCountry
     */
    public function testGetCountry($bin, $data, $countryCode): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(Response::HTTP_OK);

        $response->expects($this->once())
            ->method('getContent')
            ->willReturn($data);

        $this->client->expects($this->once())->method('request')
            ->with('GET', self::URL . $bin)
            ->willReturn($response);

        $country = $this->countryByBin->getCountry($bin);

        $this->assertSame($country, $countryCode);
    }


    public function testGetCountryIncorrectResponse(): void
    {
        $bin = "123";
        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(Response::HTTP_INTERNAL_SERVER_ERROR);

        $response->expects($this->never())->method('getContent');

        $this->client->expects($this->once())->method('request')
            ->with('GET', self::URL . $bin)
            ->willReturn($response);

        $this->expectException(CountryByBinException::class);
        $this->expectExceptionMessage('Bin not respond correctly');
        $this->countryByBin->getCountry($bin);

    }

    public function testGetCountryIncorrectResponseGetContent(): void
    {
        $bin = "123";
        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(Response::HTTP_OK);

        $response->expects($this->once())->method('getContent')->willReturn('');

        $this->client->expects($this->once())->method('request')
            ->with('GET', self::URL . $bin)
            ->willReturn($response);

        $this->expectException(CountryByBinException::class);
        $this->expectExceptionMessage('Bin wrong respond');
        $this->countryByBin->getCountry($bin);
    }

    private function dataBinCountry(): array
    {
        return [
            [
                "111111",
                '{
                    "Status":"SUCCESS",
                    "Scheme":"VISA",
                    "Type":"DEBIT",
                    "Issuer":"DJURSLANDS BANK",
                    "CardTier":"DANKORT",
                    "Country":{"A2":"DK","A3":"DNK","N3":"208","ISD":"45","Name":"Denmark","Cont":"Europe"},
                    "Luhn":true
                }',
                "DK"
            ],
            [
                "22222",
                '{
                    "Status":"SUCCESS",
                    "Scheme":"VISA",
                    "Type":"CREDIT",
                    "Issuer":"CREDIT SAISON CO., LTD.",
                    "CardTier":"CLASSIC",
                    "Country":{"A2":"JP","A3":"JPN","N3":"392","ISD":"81","Name":"Japan","Cont":"Asia"},
                    "Luhn":true
                }',
                "JP"
            ],
            [
                "333333",
                '{
                    "Status":"SUCCESS",
                    "Scheme":"VISA",
                    "Type":"CREDIT",
                    "Issuer":"VERMONT NATIONAL BANK",
                    "CardTier":null,
                    "Country":{"A2":"US","A3":"USA","N3":"840","ISD":"1","Name":"United States","Cont":"North America"},
                    "Luhn":true
                }',
                "US"
            ]
        ];
    }
}
