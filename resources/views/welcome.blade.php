<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Retreat Planner 3000</title>
    <script src="https://cdn.jsdelivr.net/npm/maplibre-gl@4.1.0/dist/maplibre-gl.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/maplibre-gl@4.1.0/dist/maplibre-gl.css" rel="stylesheet" />
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.7/dist/cdn.min.js"></script>
    <style>
        body {
            font-family: system-ui, -apple-system, sans-serif;
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
    <div id="map" x-data="mapApp()"></div>

    <script>
        function mapApp() {
            return {
                map: null,
                venues: [],
                markers: [],
                
                init() {
                    this.initMap();
                    this.loadVenues();
                },
                
                initMap() {
                    this.map = new maplibregl.Map({
                        container: 'map',
                        style: 'https://maps-assets.geocod.io/styles/geocodio.json',
                        center: [8.50132302037918, 60.85882296535163],
                        zoom: 4,
                        preserveDrawingBuffer: true
                    });
                    
                    this.map.addControl(new maplibregl.NavigationControl(), 'top-right');
                },
                
                loadVenues() {
                    fetch('/api/venues')
                        .then(response => response.json())
                        .then(data => {
                            this.venues = data;
                            this.addMarkersToMap();
                        })
                        .catch(error => console.error('Error loading venues:', error));
                },
                
                addMarkersToMap() {
                    // Clear existing markers
                    this.markers.forEach(marker => marker.remove());
                    this.markers = [];
                    
                    // Add new markers
                    this.venues.forEach(venue => {
                        if (venue.latitude && venue.longitude) {
                            const el = document.createElement('div');
                            el.className = 'marker';
                            el.style.width = '25px';
                            el.style.height = '25px';
                            el.style.backgroundImage = 'url(https://cdn.maplibre.org/images/marker.svg)';
                            el.style.backgroundSize = 'cover';
                            
                            const popup = new maplibregl.Popup({ offset: 25 })
                                .setHTML(this.createPopupContent(venue));
                            
                            const marker = new maplibregl.Marker(el)
                                .setLngLat([venue.longitude, venue.latitude])
                                .setPopup(popup)
                                .addTo(this.map);
                            
                            this.markers.push(marker);
                        }
                    });
                    
                    if (this.venues.length > 0 && this.venues[0].latitude && this.venues[0].longitude) {
                        this.map.flyTo({
                            center: [this.venues[0].longitude, this.venues[0].latitude],
                            zoom: 6
                        });
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
                    
                    return `
                        <div class="venue-popup">
                            <h3>${venue.name}</h3>
                            <div class="venue-details">
                                <p>${venue.address || ''} ${venue.city || ''} ${venue.state || ''} ${venue.country || ''}</p>
                                <p>Rooms: ${venue.rooms || 'N/A'}</p>
                                <p><a href="${venue.url}" target="_blank">View Details</a></p>
                            </div>
                            ${imagesHtml}
                        </div>
                    `;
                }
            };
        }
    </script>
</body>
</html>