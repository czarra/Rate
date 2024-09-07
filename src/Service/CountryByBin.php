<?php
declare(strict_types=1);

namespace App\Service;

use App\Exception\CountryByBinException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class CountryByBin implements CountryByBinInterface
{

    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly string $url,
    ) {
    }

    /**
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws TransportExceptionInterface
     * @throws CountryByBinException
     */
    public function getCountry(string $bin): string
    {

        $response = $this->client->request(
            'GET',
            $this->url . $bin
        );

        if($response->getStatusCode() !== Response::HTTP_OK){
            throw new CountryByBinException('Bin not respond correctly');
        }
        $content = $response->getContent();

        $binList = json_decode($content);

        if (empty($binList) || !isset($binList->Country) || !isset($binList->Country->A2)) {
            throw new CountryByBinException('Bin wrong respond');
        }

        return $binList->Country->A2;
    }
}
