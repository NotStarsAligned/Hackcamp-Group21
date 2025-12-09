<!DOCTYPE html>
<html>
<head>
    <title>Broke boy map API</title>

    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

    <!-- Map borders -->

    <style>
        #map {
            height: 300px;
            width: 30%;
        }
    </style>
</head>
<body>

<h2>Tile Plan UK Tracking</h2>

<div id="map"></div>

<script>
    // Manchester location
    var lat = 53.4808;
    var lng = -2.2426;

    // Create map
    var map = L.map('map').setView([lat, lng], 13);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 23
    }).addTo(map);

    // Marker for manchester


    L.marker([lat, lng]).addTo(map)
        .bindPopup("Manchester")
        .openPopup();
</script>

</body>
</html>
