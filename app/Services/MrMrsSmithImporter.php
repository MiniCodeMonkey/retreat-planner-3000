<?php

namespace App\Services;

use App\Models\Venue;
use DOMElement;
use Illuminate\Support\Collection;

class MrMrsSmithImporter extends VenueSourceImporter
{
    public const REGIONS = ['north-america.united-states', 'north-america.canada'];

    public function import(): void
    {
        foreach (self::REGIONS as $region) {
            $this->fetchLocations($region)->each(function ($location) {
                echo $location->name.PHP_EOL;

                $venue = Venue::updateOrCreate([
                    'source' => 'Mr & Mrs Smith',
                    'external_id' => $location->property_id,
                ], [
                    'url' => 'https://www.mrandmrssmith.com/luxury-hotels/'.$location->urlname,
                    'name' => $location->name,
                    'latitude' => $location->hotel->map_lat,
                    'longitude' => $location->hotel->map_long,
                ]);

                $this->fetchDetails($venue);
            });
        }
    }

    private function fetchLocations(string $region): Collection
    {
        $url = 'https://www.mrandmrssmith.com/maps/all?'.http_build_query([
            'search_tags' => [
                'destination.destination:'.$region,
            ],
            's' => ['adults' => 2],
            'property_type' => 'all',
        ]);

        $response = $this->httpClient->request('GET', $url);
        $content = $response->getContent();

        $json = json_decode($content);

        return collect($json);
    }

    public function saveImages(Venue $venue, $images): void
    {
        $venue->images()->delete();
        foreach ($images as $image) {
            $url = $image->getAttribute('src');
            if ($url) {
                $venue->images()->create([
                    'url' => $url,
                ]);
            }
        }
    }

    private function fetchDetails(Venue $venue): void
    {
        $crawler = $this->browser->request('GET', $venue->url);

        foreach ($crawler->filter('.boxStyling-content h3') as $contentBox) {
            if (str_contains($contentBox->textContent, 'Rooms')) {
                $venue->rooms = $this->getSiblingNumeric($contentBox);
            } elseif (str_contains($contentBox->textContent, 'Floors')) {
                $venue->floors = $this->getSiblingNumeric($contentBox);
            }
        }

        $venue->save();

        $this->saveImages($venue, $crawler->filter('.slick-track img'));
    }

    private function getSiblingNumeric(DOMElement $node): int
    {
        $nextSibling = (int) $node->nextSibling->textContent;
        if ($nextSibling !== 0) {
            return $nextSibling;
        }

        return (int) $node->nextSibling->nextSibling->textContent;
    }
}
