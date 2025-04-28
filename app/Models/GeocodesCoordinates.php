<?php

namespace App\Models;

use Exception;
use Geocodio;

trait GeocodesCoordinates
{
    protected static function bootGeocodesCoordinates(): void
    {
        static::saving(static function ($model) {
            if ($model->isDirty('latitude') || $model->isDirty('longitude')) {
                $model->handleCoordinatesChanged();
            }

            if ($model->isDirty('address')) {
                $model->handleAddressChanged();
            }
        });
    }

    protected function handleCoordinatesChanged(): void
    {
        try {
            $response = Geocodio::reverse([$this->latitude, $this->longitude]);
        } catch (Exception) {
            return;
        }

        $firstResult = $response['results'][0] ?? null;

        $this->address = $firstResult['formatted_address'] ?? null;
        $this->city = $firstResult['address_components']['city'] ?? null;
        $this->state = $firstResult['address_components']['state'] ?? null;
        $this->country = $firstResult['address_components']['country'] ?? null;
        $this->accuracy = $firstResult['accuracy'] ?? null;
        $this->accuracy_type = $firstResult['accuracy_type'] ?? null;
    }

    protected function handleAddressChanged(): void
    {
        try {
            $response = Geocodio::geocode($this->address);
        } catch (Exception) {
            return;
        }

        $firstResult = $response['results'][0] ?? null;

        $this->latitude = $firstResult['location']['lat'] ?? null;
        $this->longitude = $firstResult['location']['lng'] ?? null;
        $this->city = $firstResult['address_components']['city'] ?? null;
        $this->state = $firstResult['address_components']['state'] ?? null;
        $this->country = $firstResult['address_components']['country'] ?? null;
        $this->accuracy = $firstResult['accuracy'] ?? null;
        $this->accuracy_type = $firstResult['accuracy_type'] ?? null;
    }
}
