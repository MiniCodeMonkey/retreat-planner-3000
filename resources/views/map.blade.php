<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Retreat Planner 3000</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body {
            margin: 0;
            padding: 0;
            height: 100vh;
            width: 100vw;
        }

        #map {
            width: 100%;
            height: 100vh;
        }

        .maplibregl-popup {
            max-width: 400px;
        }

        .venue-popup {
            padding: 10px;
        }

        .venue-popup h3 {
            margin-top: 0;
            margin-bottom: 10px;
        }

        .venue-details {
            font-size: 14px;
            margin-bottom: 10px;
        }

        .venue-images {
            display: flex;
            overflow-x: auto;
            gap: 5px;
            margin-top: 10px;
        }

        .venue-image {
            flex: 0 0 auto;
            width: 100px;
            height: 75px;
            object-fit: cover;
            border-radius: 4px;
        }
    </style>
</head>
<body>
<div id="map" x-data="mapApp()">
    <!-- Left sidebar: Filters -->
    <div class="absolute top-4 left-4 z-10 bg-white p-4 rounded-lg shadow-lg w-72">
        <div class="space-y-3">
            <div class="flex justify-between items-center">
                <h3 class="text-lg font-medium text-gray-900">Filter Venues</h3>
                <div class="flex items-center">
                    <span
                        class="px-2 py-1 bg-gray-100 rounded-md text-sm font-medium flex items-center"
                        :class="getResultsCountClass()"
                    >
                        <span x-text="`${venues.length}/${allVenues.length} results`"></span>
                        <template x-if="venues.length === 0 && allVenues.length > 0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="ml-1 h-4 w-4 text-amber-500" fill="none"
                                 viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </template>
                    </span>
                </div>
            </div>
            <div>
                <label for="min-rooms" class="block text-sm font-medium text-gray-700">Minimum Rooms</label>
                <div class="mt-1">
                    <input
                        type="number"
                        id="min-rooms"
                        x-model="minRooms"
                        min="0"
                        class="p-2 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md"
                        placeholder="Minimum rooms"
                    >
                </div>
            </div>
            <div>
                <label for="max-rooms" class="block text-sm font-medium text-gray-700">Maximum Rooms</label>
                <div class="mt-1">
                    <input
                        type="number"
                        id="max-rooms"
                        x-model="maxRooms"
                        min="0"
                        class="p-2 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md"
                        placeholder="Maximum rooms"
                    >
                </div>
            </div>
            <div>
                <label for="max-airport-distance" class="block text-sm font-medium text-gray-700">Max Airport Distance
                    (miles)</label>
                <div class="mt-1">
                    <input
                        type="number"
                        id="max-airport-distance"
                        x-model="maxAirportDistance"
                        min="0"
                        class="p-2 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md"
                        placeholder="Max distance to airport"
                    >
                </div>
            </div>
            <div class="flex items-center">
                <input
                    type="checkbox"
                    id="hide-filtered"
                    x-model="hideFilteredVenues"
                    @change="updateFilteredVenuesVisibility()"
                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                >
                <label for="hide-filtered" class="ml-2 block text-sm text-gray-700">
                    Hide filtered venues
                </label>
            </div>
            <div class="flex space-x-2">
                <button
                    type="button"
                    @click="applyFilter()"
                    class="flex-1 inline-flex justify-center items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                >
                    Apply Filter
                </button>
                <button
                    type="button"
                    @click="resetFilter()"
                    class="flex-1 inline-flex justify-center items-center px-3 py-2 border border-gray-300 text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                >
                    Reset
                </button>
            </div>
        </div>
    </div>

    <!-- Right drawer: Venue details -->
    <div
        class="fixed top-0 right-0 z-20 h-full w-80 bg-white shadow-lg transform transition-transform duration-300 ease-in-out overflow-y-auto"
        :class="selectedVenue ? 'translate-x-0' : 'translate-x-full'"
    >
        <div class="p-4">
            <template x-if="selectedVenue">
                <div>
                    <!-- Venue header with close button -->
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-bold" x-text="selectedVenue.name"></h2>
                        <button
                            @click="closeVenuePanel()"
                            class="text-gray-500 hover:text-gray-700 focus:outline-none"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                                 stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Venue images -->
                    <div class="mb-4">
                        <template x-if="selectedVenue.images && selectedVenue.images.length > 0">
                            <div class="flex overflow-x-auto gap-2 pb-2">
                                <template x-for="image in selectedVenue.images" :key="image.id">
                                    <img
                                        :src="image.url"
                                        :alt="selectedVenue.name"
                                        class="h-24 w-32 object-cover rounded-md flex-shrink-0"
                                    />
                                </template>
                            </div>
                        </template>
                    </div>

                    <!-- Venue details -->
                    <div class="space-y-3">
                        <!-- Location -->
                        <div>
                            <h3 class="text-sm uppercase text-gray-500 font-medium">Location</h3>
                            <p class="text-gray-700">
                                <span x-text="selectedVenue.address || ''"></span>
                                <span x-if="selectedVenue.city" x-text="selectedVenue.city + ','"></span>
                                <span x-text="selectedVenue.state || ''"></span>
                                <span x-text="selectedVenue.country || ''"></span>
                            </p>
                        </div>

                        <!-- Room info -->
                        <div>
                            <h3 class="text-sm uppercase text-gray-500 font-medium">Venue Details</h3>
                            <p class="text-gray-700">
                                <span x-text="`Rooms: ${selectedVenue.rooms || 'N/A'}`"></span>
                                <template x-if="selectedVenue.floors">
                                    <span x-text="` • Floors: ${selectedVenue.floors}`"></span>
                                </template>
                            </p>
                        </div>

                        <!-- Nearby airports -->
                        <template x-if="selectedVenue.airports && selectedVenue.airports.length > 1">
                            <div>
                                <h3 class="text-sm uppercase text-gray-500 font-medium">Nearby Airports</h3>
                                <ul class="text-sm text-gray-700 space-y-1 mt-1">
                                    <template x-for="airport in getSortedAirports(selectedVenue)" :key="airport.id">
                                        <li>
                                            <span
                                                :class="airport.pivot.is_nearest ? 'font-semibold text-emerald-600' : ''"
                                                x-text="`${airport.iata_code} - ${airport.municipality || 'Unknown'} (${Math.round(airport.pivot.distance_miles)} miles)`"
                                            ></span>
                                        </li>
                                    </template>
                                </ul>
                            </div>
                        </template>

                        <!-- External link -->
                        <div class="pt-2">
                            <a
                                :href="selectedVenue.url"
                                target="_blank"
                                class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                            >
                                View Details
                                <svg xmlns="http://www.w3.org/2000/svg" class="ml-1 h-4 w-4" fill="none"
                                     viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('mapApp', () => ({
            map: null,
            venues: [],
            allVenues: [],
            markers: [],
            minRooms: 15,
            maxRooms: 25,
            maxAirportDistance: 50,
            hideFilteredVenues: false,
            airportsLoaded: false,
            selectedVenue: null,

            init() {
                this.loadSavedFilter();
                this.initMap();
                this.loadVenues();
            },

            loadSavedFilter() {
                // Load filter values from localStorage if they exist
                const savedMinRooms = localStorage.getItem('minRooms');
                if (savedMinRooms !== null) {
                    this.minRooms = parseInt(savedMinRooms, 10);
                }

                const savedMaxRooms = localStorage.getItem('maxRooms');
                if (savedMaxRooms !== null) {
                    this.maxRooms = parseInt(savedMaxRooms, 10);
                }

                const savedMaxAirportDistance = localStorage.getItem('maxAirportDistance');
                if (savedMaxAirportDistance !== null) {
                    this.maxAirportDistance = parseInt(savedMaxAirportDistance, 10);
                }

                const hideFilteredVenues = localStorage.getItem('hideFilteredVenues');
                if (hideFilteredVenues !== null) {
                    this.hideFilteredVenues = hideFilteredVenues === 'true';
                }
            },

            saveFilter() {
                // Save the current filter values to localStorage
                localStorage.setItem('minRooms', this.minRooms);

                if (this.maxRooms !== null) {
                    localStorage.setItem('maxRooms', this.maxRooms);
                } else {
                    localStorage.removeItem('maxRooms');
                }

                if (this.maxAirportDistance !== null) {
                    localStorage.setItem('maxAirportDistance', this.maxAirportDistance);
                } else {
                    localStorage.removeItem('maxAirportDistance');
                }

                localStorage.setItem('hideFilteredVenues', this.hideFilteredVenues);
            },

            initMap() {
                this.map = new window.maplibregl.Map({
                    container: 'map',
                    style: 'https://maps-assets.geocod.io/styles/geocodio.json',
                    center: [-99.811862, 41.543301],
                    zoom: 4,
                    preserveDrawingBuffer: true
                });

                this.map.addControl(new window.maplibregl.NavigationControl(), 'top-right');

                // Ensure map style is fully loaded before we try to add layers
                this.map.on('load', async () => {
                    try {
                        // Add airport icon and load airports
                        const image = await this.map.loadImage('/images/airport.png');
                        this.map.addImage('airport-icon', image.data);
                        this.loadAirports();
                    } catch (error) {
                        console.error('Error loading airport icon:', error);
                    }

                    // Add venue markers if we have venues
                    if (this.venues.length > 0) {
                        this.addMarkersToMap();
                    }
                });
            },

            loadAirports() {
                if (this.airportsLoaded) return;

                fetch('/airports.geojson')
                    .then(response => response.json())
                    .then(data => {
                        // Add the airports source
                        if (this.map.getSource('airports')) {
                            this.map.removeSource('airports');
                        }

                        this.map.addSource('airports', {
                            type: 'geojson',
                            data: data
                        });

                        // Add the airports layer
                        if (this.map.getLayer('airports-layer')) {
                            this.map.removeLayer('airports-layer');
                        }

                        // Use icon for airports with text labels using available Noto Sans fonts
                        this.map.addLayer({
                            id: 'airports-layer',
                            type: 'symbol',
                            source: 'airports',
                            layout: {
                                'icon-image': 'airport-icon',
                                'icon-size': 0.3,
                                'icon-allow-overlap': true,
                                'text-field': ['get', 'iata_code'],
                                'text-font': ['Noto Sans Bold'],
                                'text-offset': [0, 1],
                                'text-anchor': 'top',
                                'text-size': 10,
                                'visibility': 'visible',
                                'icon-ignore-placement': true,
                                'text-ignore-placement': true,
                                'symbol-z-order': 'source',
                                'symbol-sort-key': 1
                            },
                            paint: {
                                'text-color': '#404040',
                                'text-halo-color': '#fff',
                                'text-halo-width': 2
                            },
                            minzoom: 0,
                            maxzoom: 22
                        });

                        // Add click event to show airport info
                        this.map.on('click', 'airports-layer', (e) => {
                            const properties = e.features[0].properties;
                            const coordinates = e.features[0].geometry.coordinates.slice();

                            const popupContent = `
                                <div class="venue-popup">
                                    <h3>${properties.iata_code} Airport</h3>
                                    <div class="venue-details">
                                        <p>Location: ${properties.municipality}, ${properties.iso_country}</p>
                                    </div>
                                </div>
                            `;

                            new window.maplibregl.Popup()
                                .setLngLat(coordinates)
                                .setHTML(popupContent)
                                .addTo(this.map);
                        });

                        // Change cursor on hover
                        this.map.on('mouseenter', 'airports-layer', () => {
                            this.map.getCanvas().style.cursor = 'pointer';
                        });

                        this.map.on('mouseleave', 'airports-layer', () => {
                            this.map.getCanvas().style.cursor = '';
                        });

                        this.airportsLoaded = true;
                    })
                    .catch(error => console.error('Error loading airports:', error));
            },

            loadVenues() {
                fetch('/api/venues')
                    .then(response => response.json())
                    .then(data => {
                        this.allVenues = data;
                        this.applyFilter();
                    })
                    .catch(error => console.error('Error loading venues:', error));
            },

            applyFilter() {
                // Mark each venue as matching filter or not
                this.allVenues = this.allVenues.map(venue => {
                    // Determine if venue matches filter criteria
                    let matchesFilter = true;

                    // Check if venue has room information
                    if (!venue.rooms) {
                        matchesFilter = false;
                    }

                    // Check minimum rooms requirement
                    if (matchesFilter && this.minRooms && venue.rooms < this.minRooms) {
                        matchesFilter = false;
                    }

                    // Check maximum rooms requirement
                    if (matchesFilter && this.maxRooms && venue.rooms > this.maxRooms) {
                        matchesFilter = false;
                    }

                    // Check maximum airport distance requirement
                    if (matchesFilter && this.maxAirportDistance && venue.nearest_airport_distance > this.maxAirportDistance) {
                        matchesFilter = false;
                    }

                    // Add matchesFilter flag to venue object
                    return {...venue, matchesFilter};
                });

                // Active venues are the ones matching the filter
                this.venues = this.allVenues.filter(venue => venue.matchesFilter);

                // Save the filter to localStorage
                this.saveFilter();

                // Update the map with all venues (filtered and non-filtered)
                this.addMarkersToMap();
            },

            resetFilter() {
                this.minRooms = 15;
                this.maxRooms = 25;
                this.maxAirportDistance = 50;
                this.hideFilteredVenues = false;
                localStorage.setItem('minRooms', this.minRooms);
                localStorage.setItem('maxRooms', this.maxRooms);
                localStorage.setItem('maxAirportDistance', this.maxAirportDistance);
                localStorage.setItem('hideFilteredVenues', this.hideFilteredVenues);

                // Apply the default filters
                this.applyFilter();
            },

            addMarkersToMap() {
                // Clear existing markers
                this.markers.forEach(marker => marker.remove());
                this.markers = [];

                // Remove existing layers and sources if they exist
                if (this.map.getLayer('venue-points-filtered')) {
                    this.map.removeLayer('venue-points-filtered');
                }
                if (this.map.getLayer('venue-points-active')) {
                    this.map.removeLayer('venue-points-active');
                }
                if (this.map.getSource('venues')) {
                    this.map.removeSource('venues');
                }

                // Only proceed if the map is loaded and we have venues
                if (!this.map.isStyleLoaded() || this.allVenues.length === 0) return;

                // Create GeoJSON data from all venues
                const geojson = {
                    type: 'FeatureCollection',
                    features: this.allVenues.map(venue => {
                        if (venue.latitude && venue.longitude) {
                            return {
                                type: 'Feature',
                                geometry: {
                                    type: 'Point',
                                    coordinates: [venue.longitude, venue.latitude]
                                },
                                properties: {
                                    id: venue.id,
                                    name: venue.name,
                                    rooms: venue.rooms,
                                    matchesFilter: venue.matchesFilter,
                                    popup: this.createPopupContent(venue)
                                }
                            };
                        }
                        return null;
                    }).filter(feature => feature !== null)
                };

                // Add the venues source
                this.map.addSource('venues', {
                    type: 'geojson',
                    data: geojson
                });

                // Add a circle layer for filtered-out venues (grey)
                this.map.addLayer({
                    id: 'venue-points-filtered',
                    type: 'circle',
                    source: 'venues',
                    filter: ['!=', ['get', 'matchesFilter'], true],
                    paint: {
                        'circle-radius': 6,
                        'circle-color': '#cccccc',
                        'circle-stroke-width': 1,
                        'circle-stroke-color': '#ffffff',
                        'circle-opacity': 0.6
                    },
                    layout: {
                        'visibility': this.hideFilteredVenues ? 'none' : 'visible'
                    },
                    minzoom: 0,
                    maxzoom: 22
                });

                // Add a circle layer for active venues (blue)
                this.map.addLayer({
                    id: 'venue-points-active',
                    type: 'circle',
                    source: 'venues',
                    filter: ['==', ['get', 'matchesFilter'], true],
                    paint: {
                        'circle-radius': 6,
                        'circle-color': '#4A8DF6',
                        'circle-stroke-width': 2,
                        'circle-stroke-color': '#ffffff'
                    },
                    layout: {
                        'visibility': 'visible'
                    },
                    minzoom: 0,
                    maxzoom: 22
                });

                // Add click event to show venue details in side panel for active venues
                this.map.on('click', 'venue-points-active', (e) => {
                    const coordinates = e.features[0].geometry.coordinates.slice();
                    const venueId = e.features[0].properties.id;

                    // Find the venue data
                    const venue = this.allVenues.find(v => v.id === venueId);

                    // Show connections to nearby airports
                    this.showAirportConnections(venue, coordinates);

                    // Show venue in side panel
                    this.selectedVenue = venue;
                });

                // Add click event to show venue details in side panel for filtered venues (grey)
                this.map.on('click', 'venue-points-filtered', (e) => {
                    const coordinates = e.features[0].geometry.coordinates.slice();
                    const venueId = e.features[0].properties.id;

                    // Find the venue data
                    const venue = this.allVenues.find(v => v.id === venueId);

                    // Show connections to nearby airports
                    this.showAirportConnections(venue, coordinates);

                    // Show venue in side panel
                    this.selectedVenue = venue;
                });

                // Change cursor on hover for active venues
                this.map.on('mouseenter', 'venue-points-active', () => {
                    this.map.getCanvas().style.cursor = 'pointer';
                });

                this.map.on('mouseleave', 'venue-points-active', () => {
                    this.map.getCanvas().style.cursor = '';
                });

                // Change cursor on hover for filtered venues
                this.map.on('mouseenter', 'venue-points-filtered', () => {
                    this.map.getCanvas().style.cursor = 'pointer';
                });

                this.map.on('mouseleave', 'venue-points-filtered', () => {
                    this.map.getCanvas().style.cursor = '';
                });

                // Clear airport connections and close panel when clicking on map (not on a venue or airport)
                this.map.on('click', (e) => {
                    // Check if the click is on a venue or airport feature
                    const features = this.map.queryRenderedFeatures(e.point, {
                        layers: ['venue-points-active', 'venue-points-filtered', 'airports-layer']
                    });

                    // If not clicking on a feature, clear connections and close panel
                    if (features.length === 0) {
                        this.clearAirportConnections();
                        this.closeVenuePanel();
                    }
                });
            },

            showAirportConnections(venue, coordinates) {
                // If venue doesn't have airports or longitude/latitude, return
                if (!venue || !venue.airports || !venue.latitude || !venue.longitude) {
                    this.clearAirportConnections();
                    return;
                }

                // Clear previous connections
                this.clearAirportConnections();

                // Find the nearest airport for styling purposes
                const nearestAirport = venue.airports.find(a => a && a.pivot && a.pivot.is_nearest);

                // Create a source for ALL airports (including the nearest one)
                const allAirports = venue.airports ? [...venue.airports] : [];
                
                // Keep separate reference to nearby airports (non-nearest) for logging
                const nearbyAirports = allAirports.filter(a => a && a.pivot && !a.pivot.is_nearest);

                // Continue with showing connections for ALL airports
                if (allAirports.length > 0) {
                    console.log(`Adding connections to ${allAirports.length} airports (${nearbyAirports.length} nearby + nearest)`);

                    // Create connections for ALL airports
                    const features = allAirports.map(airport => {
                        if (!airport || !airport.pivot || typeof airport.pivot.distance_miles === 'undefined' ||
                            !airport.longitude || !airport.latitude || !airport.iata_code) {
                            console.error('Invalid airport data for line:', JSON.stringify({
                                airport_id: airport?.id,
                                iata_code: airport?.iata_code,
                                pivot: airport?.pivot,
                                longitude: airport?.longitude,
                                latitude: airport?.latitude
                            }));
                            return null;
                        }

                        return {
                            type: 'Feature',
                            geometry: {
                                type: 'LineString',
                                coordinates: [
                                    [venue.longitude, venue.latitude],
                                    [airport.longitude, airport.latitude]
                                ]
                            },
                            properties: {
                                distance: airport.pivot.distance_miles,
                                distance_text: `${Math.round(airport.pivot.distance_miles)} miles`,
                                airport: airport.iata_code,
                                is_nearest: !!airport.pivot.is_nearest
                            }
                        };
                    }).filter(feature => feature !== null);

                    // Add all airports connections
                    if (this.map.getSource('all-airports-connections')) {
                        this.map.removeSource('all-airports-connections');
                    }

                    // Only add the source and layer if we have valid features
                    if (features && features.length > 0) {
                        this.map.addSource('all-airports-connections', {
                            type: 'geojson',
                            data: {
                                type: 'FeatureCollection',
                                features: features
                            }
                        });

                        // Add all airports connection layer
                        if (this.map.getLayer('all-airports-lines')) {
                            this.map.removeLayer('all-airports-lines');
                        }

                        this.map.addLayer({
                            id: 'all-airports-lines',
                            type: 'line',
                            source: 'all-airports-connections',
                            layout: {
                                'line-join': 'round',
                                'line-cap': 'round',
                                'visibility': 'visible'
                            },
                            paint: {
                                // Use conditional styling to make the nearest airport line green
                                'line-color': [
                                    'case',
                                    ['==', ['get', 'is_nearest'], true], '#10b981', // emerald-500 for nearest
                                    '#4A8DF6' // blue for other airports
                                ],
                                'line-width': [
                                    'case',
                                    ['==', ['get', 'is_nearest'], true], 2.5, // thicker for nearest
                                    2 // normal for other airports
                                ],
                                'line-dasharray': [1, 1]
                            },
                            minzoom: 0,
                            maxzoom: 22
                        });
                    }

                    // Add distance labels for all airports
                    if (this.map.getLayer('all-airports-distance-labels')) {
                        this.map.removeLayer('all-airports-distance-labels');
                    }

                    // Place distance labels directly on the line connections
                    if (this.map.getSource('all-airports-connections')) {
                        this.map.addLayer({
                            id: 'all-airports-distance-labels',
                            type: 'symbol',
                            source: 'all-airports-connections',
                            layout: {
                                'symbol-placement': 'line-center',
                                'text-field': ['get', 'distance_text'],
                                'text-font': ['Noto Sans Bold'],
                                'text-size': 12,
                                'text-allow-overlap': true,
                                'text-ignore-placement': true,
                                'text-offset': [0, -0.5],
                                'text-anchor': 'center',
                                'text-keep-upright': true,
                                'text-max-angle': 30,
                                'visibility': 'visible'
                            },
                            paint: {
                                'text-color': [
                                    'case',
                                    ['==', ['get', 'is_nearest'], true], '#10b981', // emerald-500 for nearest
                                    '#4A8DF6' // blue for other airports
                                ],
                                'text-halo-color': '#ffffff',
                                'text-halo-width': 2
                            },
                            minzoom: 0,
                            maxzoom: 22
                        });
                    }
                }
            },

            clearAirportConnections() {
                // Remove all airports connections and their labels
                if (this.map.getLayer('all-airports-distance-labels')) {
                    this.map.removeLayer('all-airports-distance-labels');
                }
                if (this.map.getLayer('all-airports-lines')) {
                    this.map.removeLayer('all-airports-lines');
                }
                if (this.map.getSource('all-airports-connections')) {
                    this.map.removeSource('all-airports-connections');
                }
            },

            closeVenuePanel() {
                this.selectedVenue = null;
                this.clearAirportConnections();
            },

            updateFilteredVenuesVisibility() {
                if (this.map && this.map.getLayer('venue-points-filtered')) {
                    this.map.setLayoutProperty('venue-points-filtered', 'visibility',
                        this.hideFilteredVenues ? 'none' : 'visible');
                    // Save setting to localStorage
                    localStorage.setItem('hideFilteredVenues', this.hideFilteredVenues);
                }
            },

            getSortedAirports(venue) {
                if (!venue || !venue.airports) return [];

                return [...venue.airports].sort((a, b) => {
                    return a.pivot.distance_miles - b.pivot.distance_miles;
                });
            },

            getResultsCountClass() {
                if (this.venues.length === 0) {
                    return 'text-red-600 border border-red-200 bg-red-50';
                } else if (this.venues.length < 5) {
                    return 'text-amber-600 border border-amber-200 bg-amber-50';
                } else if (this.venues.length === this.allVenues.length) {
                    return 'text-gray-600 bg-gray-100';
                } else {
                    return 'text-emerald-600 border border-emerald-200 bg-emerald-50';
                }
            },

            createPopupContent(venue) {
                let imagesHtml = '';

                if (venue.images && venue.images.length > 0) {
                    imagesHtml = '<div class="venue-images">';
                    venue.images.forEach(image => {
                        imagesHtml += `<img class="venue-image" src="${image.url}" alt="${venue.name}" loading="lazy">`;
                    });
                    imagesHtml += '</div>';
                }

                // Add airport information to popup
                let airportInfo = '';

                // Add nearest airport info
                if (venue.nearest_airport_code && venue.nearest_airport_distance) {
                    airportInfo = `<p><strong>Nearest Airport:</strong> ${venue.nearest_airport_code} (${Math.round(venue.nearest_airport_distance)} miles)</p>`;
                }

                // Add nearby airports list
                if (venue.airports && venue.airports.length > 0) {
                    const sortedAirports = [...venue.airports].sort((a, b) => a.pivot.distance_miles - b.pivot.distance_miles);

                    if (sortedAirports.length > 1) {
                        airportInfo += '<p><strong>Nearby Airports:</strong></p>';
                        airportInfo += '<ul class="text-sm mt-1 pl-4">';

                        sortedAirports.slice(0, 5).forEach(airport => {
                            const isNearest = airport.pivot.is_nearest;
                            const distance = Math.round(airport.pivot.distance_miles);
                            const style = isNearest ? 'font-semibold text-emerald-600' : '';

                            airportInfo += `<li class="${style}">${airport.iata_code} - ${airport.municipality || 'Unknown'} (${distance} miles)</li>`;
                        });

                        if (sortedAirports.length > 5) {
                            airportInfo += `<li>+ ${sortedAirports.length - 5} more airports within 50 miles</li>`;
                        }

                        airportInfo += '</ul>';
                    }
                }

                let filterStatus = '';
                if (venue.matchesFilter === false) {
                    filterStatus = '<p class="text-red-500 font-medium">This venue does not match current filter criteria</p>';
                }

                return `
                        <div class="venue-popup">
                            <h3>${venue.name}</h3>
                            <div class="venue-details">
                                <p>${venue.address || ''} ${venue.city || ''} ${venue.state || ''} ${venue.country || ''}</p>
                                <p>Rooms: ${venue.rooms || 'N/A'}</p>
                                ${airportInfo}
                                ${filterStatus}
                                <p><a href="${venue.url}" target="_blank">View Details</a></p>
                            </div>
                            ${imagesHtml}
                        </div>
                    `;
            }
        }))
    });
</script>
</body>
</html>
