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
    <div class="absolute top-4 left-4 z-10 bg-white p-4 rounded-lg shadow-lg w-64">
        <div class="space-y-3">
            <h3 class="text-lg font-medium text-gray-900">Filter Venues</h3>
            <div>
                <label for="min-rooms" class="block text-sm font-medium text-gray-700">Minimum Rooms</label>
                <div class="mt-1">
                    <input
                        type="number"
                        id="min-rooms"
                        x-model="minRooms"
                        min="0"
                        class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md"
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
                        class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md"
                        placeholder="Maximum rooms"
                    >
                </div>
            </div>
            <button
                type="button"
                @click="applyFilter()"
                class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
                Apply Filter
            </button>
            <button
                type="button"
                @click="resetFilter()"
                class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
                Reset
            </button>
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
            minRooms: 0,
            maxRooms: null,
            airportsLoaded: false,

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
            },

            saveFilter() {
                // Save the current filter values to localStorage
                localStorage.setItem('minRooms', this.minRooms);
                if (this.maxRooms !== null) {
                    localStorage.setItem('maxRooms', this.maxRooms);
                } else {
                    localStorage.removeItem('maxRooms');
                }
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
                                'text-size': 10
                            },
                            paint: {
                                'text-color': '#404040',
                                'text-halo-color': '#fff',
                                'text-halo-width': 2
                            }
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
                    
                    // Add matchesFilter flag to venue object
                    return { ...venue, matchesFilter };
                });
                
                // Active venues are the ones matching the filter
                this.venues = this.allVenues.filter(venue => venue.matchesFilter);

                // Save the filter to localStorage
                this.saveFilter();

                // Update the map with all venues (filtered and non-filtered)
                this.addMarkersToMap();
            },

            resetFilter() {
                this.minRooms = 0;
                this.maxRooms = null;
                localStorage.removeItem('minRooms');
                localStorage.removeItem('maxRooms');
                
                // Mark all venues as matching
                this.allVenues = this.allVenues.map(venue => ({ ...venue, matchesFilter: true }));
                this.venues = this.allVenues;
                
                this.addMarkersToMap();
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
                    }
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
                    }
                });

                // Add click event to show popups for active venues
                this.map.on('click', 'venue-points-active', (e) => {
                    const coordinates = e.features[0].geometry.coordinates.slice();
                    const popupContent = e.features[0].properties.popup;

                    // Create popup
                    new window.maplibregl.Popup()
                        .setLngLat(coordinates)
                        .setHTML(popupContent)
                        .addTo(this.map);
                });
                
                // Add click event to show popups for filtered venues (grey)
                this.map.on('click', 'venue-points-filtered', (e) => {
                    const coordinates = e.features[0].geometry.coordinates.slice();
                    const popupContent = e.features[0].properties.popup;

                    // Create popup
                    new window.maplibregl.Popup()
                        .setLngLat(coordinates)
                        .setHTML(popupContent)
                        .addTo(this.map);
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
