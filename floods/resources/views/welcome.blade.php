<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Laravel</title>

        <!-- leafleet CSS -->
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.3.4/dist/leaflet.css"
            integrity="sha512-puBpdR0798OZvTTbP4A8Ix/l+A4dHDD0DGqYW6RQ+9jxkRFclaxxQb/SJAWZfWAkuyeQUytO7+7N4QKrDh+drA=="
            crossorigin=""/>

        <!-- routing module CSS -->
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.2.0/dist/leaflet.css" />
        <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@latest/dist/leaflet-routing-machine.css" />

        <!-- Fonts -->
        <link href="https://fonts.googleapis.com/css?family=Nunito:200,600" rel="stylesheet" type="text/css">

        <!-- Bootstrap -->
        <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" rel="stylesheet" type="text/css">
        
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
            #spinner {
                position: fixed;
                color: white;
                width: 100%;
                height: 100%;
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                z-index: 100000;
                background: rgba(10, 10, 10, .80);
                font-size: 28px;
                visibility: hidden;
            }
            #mapid { height: 100%; }
            .container-fluid {
                padding-top: 15px;
            }
            #spinner > .progress {
                width: 500px;
            }
        </style>
    </head>
    <body>
        <div id="spinner">
            <div>Mapa sa načítava...</div>
            <div class="progress">
                <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="width: 100%"></div>
            </div>
        </div>
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12 text-center" style="margin-bottom: 30px;">
                    <h1>Regióny Anglicka, povodňové nebezpečenstvo a ohrozenie nemocníc</h1>
                </div>
                <div class="col-md-8" style="height: 80vh;">
                    <h3>Mapová časť</h3>
                    <div id="mapid"></div>
                </div>
                <div class="col-md-4">
                    <h3>Ovládacie prvky</h3>
                    <div class="col-md-10">
                        <div class="form-group">
                            <label for="exampleInputEmail1">Veľkosť bezpečnej oblasti [m]</label>
                            <input type="number" class="form-control" id="safe-radius" value="2000" min="0" max="100000" placeholder="Veľkosť bezpečnej oblasti [m]" required>
                        </div>
                        <div class="form-check" style="padding-bottom: 15px;">
                            <input type="checkbox" class="form-check-input" id="show-route-chx">
                            <label class="form-check-label" for="show-route-chx">Zobrazovať cestu k bezpečnej nemocnici</label>
                        </div>
                        <button type="button" id="refresh-map" class="btn btn-primary">Obnoviť mapu</button>
                    </div>

                    <hr>

                    <div class="col-md-12" style="overflow: auto; height: 60vh;">
                        <div id="stats">
                        </div>
                    </div>
                </div>
            </div> 
        </div>
        
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
        let isLoading = false;
        function setIsLoading(value) {
            isLoading = value;
            $('#spinner').css('visibility', isLoading == true ? 'visible' : 'hidden' );
        }
        $( document ).ajaxStart(function() {
            setIsLoading(true);
        });

        $( document ).ajaxStop(function() {
            setIsLoading(false);
        });

        function addStats(intro, text) {
            $("#stats").append(`<div><span class="font-weight-bold">${intro}:</span> ${text}</div>`);
        }

        function clearStats() {
            $("#stats > *").remove();
            addStats('Vykreslené regióny', citiesCount);
        }

        console.log( "ready!" );
        var mymap = L.map('mapid').setView([52.68, -1.95], 7);
        L.tileLayer('https://api.tiles.mapbox.com/v4/{id}/{z}/{x}/{y}.png?access_token=pk.eyJ1Ijoic3JuaWFrIiwiYSI6ImNqb3UybW95ejBiM2MzcHM1dzV2YmJxdTAifQ.mVE4Vn_nAB72-rdboaksHA', {
            attribution: 'Map data &copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a> contributors, <a href="https://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, Imagery © <a href="https://www.mapbox.com/">Mapbox</a>',
            maxZoom: 18,
            id: 'mapbox.streets',
            accessToken: 'your.mapbox.access.token'
        }).addTo(mymap);
        L.control.scale().addTo(mymap);

        getCities(340, 0);
        
        let citiesCount = 0;
        let diameterSafeZone = 2000;
        let i = 0;

        let endangeringFloodsGroup;
        let floodsAndHospitalsGroup;

        let showingFloodsForHospitalId;
        let showingCityId;

        let routeControl;

        $('#safe-radius').on('input', function (e) {
            diameterSafeZone = e.currentTarget.value;
        });

        $('#refresh-map').on('click', function (e) {
            if (mymap.hasLayer(floodsAndHospitalsGroup)) {
                mymap.removeLayer(floodsAndHospitalsGroup);
                if (mymap.hasLayer(endangeringFloodsGroup)) {
                    mymap.removeLayer(endangeringFloodsGroup);
                }
                if (routeControl != null) {
                    routeControl.getPlan().setWaypoints([]);
                }
            }
            getFloods(showingCityId);
            getHospitals(showingCityId, diameterSafeZone);
            if (showingFloodsForHospitalId != null) {
                const hospId = showingFloodsForHospitalId;
                showingFloodsForHospitalId = null;
                getFloodsForHospital(hospId, diameterSafeZone);
                getClosestSafeHospital(showingCityId, diameterSafeZone, hospId);
            }
        });

        function getCities(limit, offset) {
            $.get("api/cities?limit=" + limit + '&offset=' + offset, function(data) {
                const count = data.length;
                if (count > 0) {
                    citiesCount+=count;
                    data.map((el) => {
                        let geoJson = JSON.parse(el.st_asgeojson);
                        id = el.id;
                        name = el.name;
                        L.geoJson(
                            geoJson, 
                            {
                                onEachFeature: (feature, layer) => {
                                    layer.feature.properties.id = id;
                                    layer.feature.properties.name = name;
                                    layer.on({
                                        click: (e) => {
                                            const id = e.target.feature.properties.id;
                                            const name = e.target.feature.properties.name;
                                            if (showingCityId == id) {
                                                return;
                                            }
                                            if (mymap.hasLayer(floodsAndHospitalsGroup)) {
                                                mymap.removeLayer(floodsAndHospitalsGroup);
                                                if (mymap.hasLayer(endangeringFloodsGroup)) {
                                                    mymap.removeLayer(endangeringFloodsGroup);
                                                }
                                                if (routeControl != null) {
                                                    routeControl.getPlan().setWaypoints([]);
                                                } 
                                            }
                                            showingCityId = id;
                                            addStats('Zobrazený región', name);
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
                } else {
                    // clearStats();
                }
                clearStats();
            });
        }

        function getFloods(cityId) {
            $.get("api/floods/" + cityId, function(data) {
                floodsAndHospitalsGroup = L.featureGroup();
                addStats('Zaznamenané povodne pre región', data.length + 'x');
                let highProbCount = 0;
                data.map((el) => {
                    let geoJson = JSON.parse(el.st_asgeojson); 
                    let prob = el.prob.toString();
                    if (prob == 'High') {
                        highProbCount++;
                    }
                    var geojsonMarkerOptions = {
                        radius: 8,
                        fillColor: prob == 'Low' ? '#0099ff' : (prob == 'Medium') ? '#ffdb4d' : '#ff5c33',
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
                addStats('Z toho veľká pravdepodobnosť povodne', highProbCount + 'x');
            });
        }

        function getHospitals(id, safeZoneDiameter) {
            $.get("api/hospitals/" + id + '?safeZoneDiameter=' + safeZoneDiameter, function(data) {
                addStats('Nemocnice v regióne', data.length);
                let endangeredCount = 0;
                data.map((el) => {
                    const geoJsonCities = JSON.parse(el.hospital);
                    const endangered = el.endangered;
                    id = el.hospital_id;
                    if (endangered) {
                        endangeredCount++;
                    }
                    var polygon = L.geoJson(
                        geoJsonCities, 
                        { 
                            style: {color: endangered == null ? 'green' : 'red'},
                            onEachFeature: (feature, layer) => { 
                                layer.feature.properties.id = id;
                                layer.on({
                                    click: (e) => {
                                        const id = e.target.feature.properties.id;
                                        if (routeControl != null) {
                                            routeControl.getPlan().setWaypoints([]);
                                        } 
                                        if (mymap.hasLayer(endangeringFloodsGroup)) {
                                            mymap.removeLayer(endangeringFloodsGroup);
                                        }

                                        if (showingFloodsForHospitalId == id) {
                                            showingFloodsForHospitalId = null;
                                            return;
                                        }
                                        getFloodsForHospital(id, diameterSafeZone);
                                        getClosestSafeHospital(showingCityId, diameterSafeZone, id);
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
                addStats('Z toho ohrozené vysokou pravdepodobnosťou povodne', endangeredCount);
            });
        }
        
        function getFloodsForHospital(hospitalId, safeZoneDiameter) {            
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

        function getClosestSafeHospital(cityId, diameter, hospitalId) {
            $.get("api/closest-safe-hospital/?cityId=" + cityId + '&safeZoneDiameter=' + diameter + '&hospitalId=' + hospitalId, function(data) {
                if (data.length > 0) {
                    $('#stats').append(`<div class="alert alert-success">Našla sa nemocnica, ktorá je v bezpečí.</div>`);
                    const el = data[0];
                    const closestHospital = JSON.parse(el.closest_hospital);
                    const endangeredHospital = JSON.parse(el.endangered_hospital);
                    const distance = JSON.parse(el.distance);
                    L.geoJson(closestHospital).addTo(floodsAndHospitalsGroup);
                    if ($('#show-route-chx').is(":checked")) {
                        routeControl = L.Routing.control({
                            waypoints: [
                                L.latLng(endangeredHospital.coordinates[1], endangeredHospital.coordinates[0]),
                                L.latLng(closestHospital.coordinates[1], closestHospital.coordinates[0])
                            ]
                        }).addTo(mymap);
                    }
                } else {
                    $('#stats').append(`<div class="alert alert-danger">V danom regióne sa nenachádza nemocnica, ktorá je v bezpečí.</div>`);
                }
            });

        }
    });
    
    </script>
    <!-- Routing JS -->
    <script src="https://unpkg.com/leaflet@1.2.0/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-routing-machine@latest/dist/leaflet-routing-machine.js"></script>
</html>
