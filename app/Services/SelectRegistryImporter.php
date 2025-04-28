<?php

namespace App\Services;

use App\Models\Venue;

class SelectRegistryImporter extends VenueSourceImporter
{
    public function import(): void
    {
        $locations = json_decode(file_get_contents(resource_path('selectregistry.json')));
        collect($locations)->each(function ($location) {
            echo $location->name.PHP_EOL;

            $venue = Venue::updateOrCreate([
                'source' => 'Select Registry',
                'external_id' => $location->url,
            ], [
                'url' => $location->url,
                'name' => $location->name,
                'rooms' => $location->room_count,
            ]);

            $this->fetchDetails($venue);
        });
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
        $addressNode = $crawler->filter('.place .address')->first();
        $venue->address = trim($addressNode->text());
        $venue->save();

        $this->saveImages($venue, $crawler->filter('.container_coll img'));

        $venue->save();
    }
}
