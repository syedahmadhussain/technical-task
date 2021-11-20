<?php

declare(strict_types=1);

namespace App\Service;

use App\ValueObject\Address;
use App\ValueObject\Coordinates;
use GuzzleHttp\Client;

class GoogleMapsService implements MapsServiceInterface
{
    public function getCoordinates(Address $address): ?Coordinates
    {
       return $this->getResponse($address);
    }

    private function buildParams(Address $address): array
    {
        $apiKey = $_ENV["GOOGLE_GEOCODING_API_KEY"];

        return [
            'query' => [
                'address' => $address->getStreet(),
                'components' => implode(
                    '|',
                    [
                        "country:{$address->getCountry()}",
                        "locality:{$address->getCity()}",
                        "postal_code:{$address->getPostcode()}",
                    ]
                ),
                'key' => $apiKey,
            ],
        ];
    }

    private function getResponse(Address $address): ?Coordinates
    {
        $params = $this->buildParams($address);

        $client = new Client();

        $response = $client->get('https://maps.googleapis.com/maps/api/geocode/json', $params);

        $data = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        if (count($data['results']) === 0) {
            return null;
        }

        $firstResult = $data['results'][0];

        return new Coordinates(
            $firstResult['geometry']['location']['lat'], $firstResult['geometry']['location']['lng']
        );
    }
}
