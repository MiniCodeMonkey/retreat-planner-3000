<?php

namespace App\Services;

use App\Models\Venue;
use DOMElement;
use Illuminate\Support\Collection;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Prism;

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
                $val = $this->getSiblingNumeric($contentBox);

                if ($val === 0) {
                    $val = $this->getRoomCountFromText($contentBox->parentNode->textContent);
                }

                if ($val !== 0) {
                    $venue->rooms = $val;
                    $venue->save();
                    break;
                }
            }
        }

        $this->saveImages($venue, $crawler->filter('.slick-track img'));

        sleep(1); // to avoid rate limiting
    }

    private function getSiblingNumeric(DOMElement $node): int
    {
        $nextSibling = (int) $node->nextSibling->textContent;
        if ($nextSibling !== 0) {
            return $nextSibling;
        }

        return (int) $node->nextSibling->nextSibling->textContent;
    }

    private function getRoomCountFromText(string $textContent): int
    {
        $response = Prism::text()
            ->using(Provider::Anthropic, 'claude-3-5-sonnet-20241022')
            ->withPrompt(<<<'EOF'
Given the following text that describes a hotel room, extract the number of rooms mentioned in the text.
Return the number of rooms as an integer and NOTHING else.

The text is:
EOF
.$textContent)
            ->asText();

        return (int) $response->text;
    }
}
