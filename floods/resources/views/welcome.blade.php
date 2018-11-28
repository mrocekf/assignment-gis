<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Laravel</title>

        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.3.4/dist/leaflet.css"
            integrity="sha512-puBpdR0798OZvTTbP4A8Ix/l+A4dHDD0DGqYW6RQ+9jxkRFclaxxQb/SJAWZfWAkuyeQUytO7+7N4QKrDh+drA=="
            crossorigin=""/>

        <!-- Fonts -->
        <link href="https://fonts.googleapis.com/css?family=Nunito:200,600" rel="stylesheet" type="text/css">

        <!-- Styles -->
        <style>
            html, body {
                background-color: #fff;
                color: #636b6f;
                font-family: 'Nunito', sans-serif;
                font-weight: 200;
                height: 100vh;
                margin: 0;
            }

            .full-height {
                height: 100vh;
            }

            .flex-center {
                align-items: center;
                display: flex;
                justify-content: center;
            }

            .position-ref {
                position: relative;
            }

            .top-right {
                position: absolute;
                right: 10px;
                top: 18px;
            }

            .content {
                text-align: center;
            }

            .title {
                font-size: 84px;
            }

            .links > a {
                color: #636b6f;
                padding: 0 25px;
                font-size: 13px;
                font-weight: 600;
                letter-spacing: .1rem;
                text-decoration: none;
                text-transform: uppercase;
            }

            .m-b-md {
                margin-bottom: 30px;
            }

            #mapid { height: 800px; }
        </style>
    </head>
    <body>
        <input type="number" id="safe-radius" value="2000" min="0" max="100000">
        <div id="mapid"></div>
    </body>
     <!-- Make sure you put this AFTER Leaflet's CSS -->
    <script src="https://unpkg.com/leaflet@1.3.4/dist/leaflet.js"
        integrity="sha512-nMMmRyTVoLYqjP9hrbed9S+FzjZHW5gY1TWCHA5ckwXZBadntCNs8kEqAWdrb9O7rxbCaA4lKTIWjDXZxflOcA=="
        crossorigin="">
    </script>
    <script
        src="https://code.jquery.com/jquery-2.2.4.min.js"
        integrity="sha256-BbhdlvQf/xTY9gja0Dq3HiwQF8LaCRTXxZKRutelT44="
        crossorigin="anonymous">
    </script>

    <script>
    $( document ).ready(function() {
        console.log( "ready!" );
        var mymap = L.map('mapid').setView([52.68, -1.95], 7);
        L.tileLayer('https://api.tiles.mapbox.com/v4/{id}/{z}/{x}/{y}.png?access_token=pk.eyJ1Ijoic3JuaWFrIiwiYSI6ImNqb3UybW95ejBiM2MzcHM1dzV2YmJxdTAifQ.mVE4Vn_nAB72-rdboaksHA', {
            attribution: 'Map data &copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a> contributors, <a href="https://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, Imagery © <a href="https://www.mapbox.com/">Mapbox</a>',
            maxZoom: 18,
            id: 'mapbox.streets',
            accessToken: 'your.mapbox.access.token'
        }).addTo(mymap);
        L.control.scale().addTo(mymap);
        getCities(115, 0);
        
        let diameterSafeZone = 2000;
        let i = 0;

        let endangeringFloodsGroup;
        let floodsAndHospitalsGroup;

        let showingFloodsForHospitalId;
        let showingCityId;

        $('#safe-radius').on('input', function (e) {
            diameterSafeZone = e.currentTarget.value;
        });

        function getCities(limit, offset) {
            $.get("api/cities?limit=" + limit + '&offset=' + offset, function(data) {
                const count = data.length;
                if (count > 0) {
                    data.map((el) => {
                        let geoJson = JSON.parse(el.st_asgeojson);
                        id = JSON.parse(el.id).toString();
                        L.geoJson(
                            geoJson, 
                            {
                                onEachFeature: (feature, layer) => {
                                    layer.feature.properties.id = id;
                                    layer.on({
                                        click: (e) => {
                                            const id = e.target.feature.properties.id;
                                            getFloods(id);
                                            getHospitals(id, diameterSafeZone);
                                            mymap.fitBounds(e.target.getBounds());
                                        }
                                    });
                                }
                            }
                        ).addTo(mymap);
                    });

                    // getCities(limit, offset + limit);
                }
                
            });
        }

        function getFloods(cityId) {
            if (mymap.hasLayer(floodsAndHospitalsGroup)) {
                mymap.removeLayer(floodsAndHospitalsGroup);
                if (mymap.hasLayer(endangeringFloodsGroup)) {
                    mymap.removeLayer(endangeringFloodsGroup);
                }
            }

            if (showingCityId == cityId) {
                showingCityId = null;
                showingFloodsForHospitalId = null;
                return;
            }
            $.get("api/floods/" + cityId, function(data) {
                floodsAndHospitalsGroup = L.featureGroup();
                data.map((el) => {
                    let geoJson = JSON.parse(el.st_asgeojson); 
                    let prob = el.prob.toString();
                    var geojsonMarkerOptions = {
                        radius: 8,
                        fillColor: prob == 'Low' ? '#0099ff' : prob == 'Medium' ? '#ffdb4d' : '#ff5c33',
                        color: "#000",
                        weight: 1,
                        opacity: 1,
                        fillOpacity: 0.8
                    };
                    L.geoJson(geoJson, {
                        pointToLayer: function (feature, latlng) {
                            return L.circleMarker(latlng, geojsonMarkerOptions);
                        }
                    }).addTo(floodsAndHospitalsGroup);
                    mymap.addLayer(floodsAndHospitalsGroup);
                });
            });
        }

        function getHospitals(id, safeZoneDiameter) {
            $.get("api/hospitals/" + id + '?safeZoneDiameter=' + safeZoneDiameter, function(data) {
                data.map((el) => {
                    const geoJsonCities = JSON.parse(el.hospital);
                    const endangered = el.endangered;
                    id = el.hospital_id;
                    var polygon = L.geoJson(
                        geoJsonCities, 
                        { 
                            style: {color: endangered == null ? 'green' : 'red'},
                            onEachFeature: (feature, layer) => { 
                                layer.feature.properties.id = id;
                                layer.on({
                                    click: (e) => {
                                        const id = e.target.feature.properties.id;
                                        getFloodsForHospital(id, diameterSafeZone);
                                    }
                                });
                            }
                        }
                    ).addTo(floodsAndHospitalsGroup);
                    const centroid = polygon.getBounds().getCenter();
                    var circ = L.circle([centroid.lat, centroid.lng], {
                        color: endangered == null ? 'green' : 'red',
                        fillColor: '#f03',
                        fillOpacity: 0.1,
                        radius: 350,
                        interactive: false
                    }).addTo(floodsAndHospitalsGroup)
                    mymap.addLayer(floodsAndHospitalsGroup);
                });
            });
        }
        
        function getFloodsForHospital(hospitalId, safeZoneDiameter) {
            if (mymap.hasLayer(endangeringFloodsGroup)) {
                mymap.removeLayer(endangeringFloodsGroup);
            }

            if (showingFloodsForHospitalId == hospitalId) {
                showingFloodsForHospitalId = null;
                return;
            }
            
            $.get("api/floods-for-hospital/?hospitalId=" + hospitalId + '&safeZoneDiameter=' + safeZoneDiameter, function(data) {
                showingFloodsForHospitalId = hospitalId;

                endangeringFloodsGroup = L.featureGroup();
                data.map((el) => {
                    const circleCoords = JSON.parse(el.flood_point_json).coordinates;
                    L.circle([circleCoords[1], circleCoords[0]], {
                        color: 'red',
                        fillColor: '#f03',
                        fillOpacity: 0.05,
                        radius: diameterSafeZone,
                        interactive: false
                    }).addTo(endangeringFloodsGroup);
                });
                mymap.addLayer(endangeringFloodsGroup);
            });
        }

    });
    
    </script>
</html>
