<?php

declare(strict_types=1);

namespace App\Service;

use App\ValueObject\Address;
use App\ValueObject\Coordinates;
use GuzzleHttp\Client;

class HereMapsService implements MapsServiceInterface
{
    public function getCoordinates(Address $address): ?Coordinates
    {
        return $this->getResponse($address);
    }

    private function getResponse(Address $address): ?Coordinates
    {
        $params = $this->buildParams($address);
        $client = new Client();

        $response = $client->get('https://geocode.search.hereapi.com/v1/geocode', $params);

        $data = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        if (count($data['items']) === 0) {
            return null;
        }

        $firstItem = $data['items'][0];

        if ($firstItem['resultType'] !== 'houseNumber') {
            return null;
        }

        return new Coordinates(
            $firstItem['position']['lat'], $firstItem['position']['lng']
        );
    }

    private function buildParams(Address $address): array
    {
        $apiKey = $_ENV["HEREMAPS_GEOCODING_API_KEY"];

        return [
            'query' => [
                'qq' => implode(
                    ';',
                    [
                        "country={$address->getCountry()}",
                        "city={$address->getCity()}",
                        "street={$address->getStreet()}",
                        "postalCode={$address->getPostcode()}",
                    ]
                ),
                'apiKey' => $apiKey,
            ],
        ];
    }
}
