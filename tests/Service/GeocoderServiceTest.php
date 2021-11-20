<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Repository\ResolvedAddressRepository;
use App\Service\CacheMapsService;
use App\Service\GeocoderService;
use App\Service\GoogleMapsService;
use App\Service\HereMapsService;
use App\ValueObject\Address;
use App\ValueObject\Coordinates;
use Generator;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

final class GeocoderServiceTest extends TestCase
{
    /**
     * @var ResolvedAddressRepository|MockInterface
     */
    private ResolvedAddressRepository $resolvedAddressRepositoryMock;

    protected function setUp(): void
    {
        $this->resolvedAddressRepositoryMock = Mockery::mock(ResolvedAddressRepository::class);
    }

    /**
     * @param array<string, bool> $services
     * @param array<string, null|Coordinates> $data
     *
     * @dataProvider geocodeDataProvider
     */
    public function testGeocode(array $services, array $data, ?Coordinates $expectedCoordinates): void
    {
        $googleMapsServiceMock = Mockery::mock(GoogleMapsService::class);
        $hereMapsServiceMock = Mockery::mock(HereMapsService::class);
        $cacheMapsServiceMock = Mockery::mock(CacheMapsService::class);

        $mapsServices = $this->buildMapsServices(
            $services,
            $googleMapsServiceMock,
            $cacheMapsServiceMock,
            $hereMapsServiceMock
        );

        $geocoderService = new GeocoderService($mapsServices, $this->resolvedAddressRepositoryMock);

        $address = $this->getAddress();

        $this->checkMapsServices(
            $services,
            $googleMapsServiceMock,
            $address,
            $data,
            $hereMapsServiceMock,
            $cacheMapsServiceMock
        );

        $this->resolvedAddressRepositoryMock->shouldReceive('saveResolvedAddress')->once();

        $coordinates = $geocoderService->geocode($address);

        if ($expectedCoordinates === null) {
            self::assertNull($coordinates);
        } else {
            self::assertInstanceOf(Coordinates::class, $coordinates);
            self::assertEquals($coordinates->getLng(), $expectedCoordinates->getLng());
            self::assertEquals($coordinates->getLat(), $expectedCoordinates->getLat());
        }
    }

    /**
     * @return Generator<string, array<array<string, mixed>>>
     */
    public function geocodeDataProvider(): Generator
    {
        $coordinates = new Coordinates(30300.333, 203.3333);
        yield 'Service (Google Maps & Here Maps) => check in HereMaps' => [
            'services' => [
                'googleMapsService' => true,
                'hereMapsService' => true,
                'cacheMapsService' => false,
            ],
            'data' => [
                'googleMapsService' => null,
                'hereMapsService' => $coordinates,
                'cacheMapsService' => null,
            ],
            $coordinates,
        ];

        yield 'Service (Cache Maps, Google Maps) => check in GoogleMap' => [
            'services' => [
                'cacheMapsService' => true,
                'googleMapsService' => true,
                'hereMapsService' => false,
            ],
            'data' => [
                'googleMapsService' => $coordinates,
                'hereMapsService' => null,
                'cacheMapsService' => null,
            ],
            $coordinates,
        ];

        yield 'Service (Cache Maps) => check in Cache Maps' => [
            'services' => [
                'cacheMapsService' => true,
                'googleMapsService' => false,
                'hereMapsService' => false,
            ],
            'data' => [
                'cacheMapsService' => $coordinates,
                'googleMapsService' => null,
                'hereMapsService' => null,
            ],
            $coordinates,
        ];

        yield 'Service (Google Maps) => check in Google Maps' => [
            'services' => [
                'cacheMapsService' => false,
                'googleMapsService' => true,
                'hereMapsService' => false,
            ],
            'data' => [
                'cacheMapsService' => null,
                'googleMapsService' => $coordinates,
                'hereMapsService' => null,
            ],
            $coordinates,
        ];

        yield 'Service (Here Maps) => check in Here Maps' => [
            'services' => [
                'cacheMapsService' => false,
                'googleMapsService' => false,
                'hereMapsService' => true,
            ],
            'data' => [
                'cacheMapsService' => null,
                'googleMapsService' => null,
                'hereMapsService' => $coordinates,
            ],
            $coordinates,
        ];

        yield 'Service (Cache Maps, Google Maps, Here Maps) => check in Here Maps' => [
            'services' => [
                'cacheMapsService' => true,
                'googleMapsService' => true,
                'hereMapsService' => true,
            ],
            'data' => [
                'cacheMapsService' => null,
                'googleMapsService' => null,
                'hereMapsService' => $coordinates,
            ],
            $coordinates,
        ];
    }

    private function getAddress(): Address
    {
        return new Address('de', 'berlin', 'einbecker', '10137');
    }

    private function buildMapsServices(
        array $services,
        MockInterface $googleMapsServiceMock,
        MockInterface $cacheMapsServiceMock,
        MockInterface $hereMapsServiceMock
    ): array {
        $mapsServices = [];
        if ($services['cacheMapsService']) {
            $mapsServices[] = $cacheMapsServiceMock;
        }
        if ($services['googleMapsService']) {
            $mapsServices[] = $googleMapsServiceMock;
        }
        if ($services['hereMapsService']) {
            $mapsServices[] = $hereMapsServiceMock;
        }

        return $mapsServices;
    }

    private function checkMapsServices(
        array $services,
        MockInterface $googleMapsServiceMock,
        Address $address,
        array $data,
        MockInterface $hereMapsServiceMock,
        MockInterface $cacheMapsServiceMock
    ): void {
        if ($services['cacheMapsService']) {
            $cacheMapsServiceMock->shouldReceive('getCoordinates')->with($address)->once()->andReturn(
                $data['cacheMapsService']
            );
        }
        if ($services['googleMapsService']) {
            $googleMapsServiceMock->shouldReceive('getCoordinates')->with($address)->once()->andReturn(
                $data['googleMapsService']
            );
        }
        if ($services['hereMapsService']) {
            $hereMapsServiceMock->shouldReceive('getCoordinates')->with($address)->once()->andReturn(
                $data['hereMapsService']
            );
        }
    }
}
