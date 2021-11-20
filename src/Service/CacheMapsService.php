<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\ResolvedAddressRepository;
use App\ValueObject\Address;
use App\ValueObject\Coordinates;

class CacheMapsService implements MapsServiceInterface
{
    private ResolvedAddressRepository $resolvedAddressRepository;

    public function __construct(ResolvedAddressRepository $resolvedAddressRepository)
    {
        $this->resolvedAddressRepository = $resolvedAddressRepository;
    }

    public function getCoordinates(Address $address): ?Coordinates
    {
        $resolvedAddress = $this->resolvedAddressRepository->getByAddress($address);

        if ($resolvedAddress === null || ($resolvedAddress->getLat() === null && $resolvedAddress->getLat() == null)) {
            return null;
        }

        return new Coordinates((float)$resolvedAddress->getLat(), (float)$resolvedAddress->getLng());
    }
}
