<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\ResolvedAddressRepository;
use App\ValueObject\Address;
use App\ValueObject\Coordinates;

final class GeocoderService implements GeocoderServiceInterface
{
    /**
     * @param MapsServiceInterface[] $mapServices
     */
    private array $mapServices;

    private ResolvedAddressRepository $resolvedAddressRepository;

    /**
     * @param MapsServiceInterface[] $mapServices
     */
    public function __construct(array $mapServices, ResolvedAddressRepository $resolvedAddressRepository)
    {
        $this->mapServices = $mapServices;
        $this->resolvedAddressRepository = $resolvedAddressRepository;
    }

    public function geocode(Address $address): ?Coordinates
    {
        $coordinates = null;
        foreach ($this->mapServices as $service) {
            $coordinates = $service->getCoordinates($address);
            if ($coordinates !== null) {
                break;
            }
        }
        $this->saveAddress($address, $coordinates);

        return $coordinates;
    }

    private function saveAddress(Address $address, ?Coordinates $coordinates): void
    {
        $this->resolvedAddressRepository->saveResolvedAddress($address, $coordinates);
    }
}
