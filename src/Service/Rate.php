<?php
declare(strict_types=1);

namespace App\Service;

use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\Response;
use App\Exception\RateException;

class Rate implements RateInterface
{

    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly string $apiKey,
        private readonly string $url
    ){

    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws RateException
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function getRates(): array
    {
        $response = $this->client->request(
            'GET',
            $this->url . $this->apiKey
        );

        if($response->getStatusCode() != Response::HTTP_OK){
            throw new RateException('Exchangeratesapi not respond correctly');
        }
        $rates = $response->toArray();

        if (empty($rates) || !isset($rates['rates'])) {
            throw new RateException('Exchangeratesapi wrong respond');
        }

        return $rates['rates'];
    }
}
