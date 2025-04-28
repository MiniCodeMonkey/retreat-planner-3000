<?php

namespace App\Services;

use App\Models\Venue;
use Illuminate\Support\Collection;

class SLHImporter extends VenueSourceImporter
{
    public const REGIONS = ['USA', 'Canada'];

    public function import(): void
    {
        foreach (self::REGIONS as $region) {
            $this->fetchLocations($region)->each(function ($location) {
                echo $location->title.PHP_EOL;

                $venue = Venue::updateOrCreate([
                    'source' => 'SLH',
                    'external_id' => $location->id,
                ], [
                    'url' => 'https://slh.com'.$location->detailUrl,
                    'name' => $location->title,
                    'latitude' => $location->location->coordinates->lat,
                    'longitude' => $location->location->coordinates->lng,
                ]);

                $this->saveImages($venue, $location->images);
                $this->fetchDetails($venue);
            });
        }
    }

    private function fetchLocations(string $region): Collection
    {
        $url = 'https://slh.com/api/slh/hotelsearchresults/gethotelsearchresults?'.http_build_query([
            'roomsList' => '',
            'sort' => 'descRelevance',
            'pageIndex' => '0',
            'resultsPerPage' => '0',
            'query' => $region,
            'viewType' => 'list',
            'regions' => $region,
        ]);

        $response = $this->httpClient->request('GET', $url);
        $content = $response->getContent();

        // Remove BOM character if present
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);

        $json = json_decode($content);

        return collect($json->items);
    }

    public function saveImages(Venue $venue, $images): void
    {
        $venue->images()->delete();
        foreach ($images as $image) {
            $url = array_values((array) $image->sources)[0] ?? null;
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

        foreach ($crawler->filter('.sc-hotel-overview__fact-label') as $factLabel) {
            $content = $factLabel->textContent;
            [$quantity, $unit] = explode(' ', $content, 2);
            if (str_contains($unit, 'Rooms')) {
                $venue->rooms = (int) $quantity;
            } elseif (str_contains($unit, 'Floors')) {
                $venue->floors = (int) $quantity;
            }
        }

        $venue->save();
    }
}
