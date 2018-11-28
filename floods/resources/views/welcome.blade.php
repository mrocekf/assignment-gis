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
        <button id="citiesBtn">Cities</button>  
        <button id="floodsBtn">Floods</button>
        <button id="portsBtn">Ports</button>
        <button id="testBtn">TEST</button>
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
        var mymap = L.map('mapid').setView([51.505, -0.09], 8);
        L.tileLayer('https://api.tiles.mapbox.com/v4/{id}/{z}/{x}/{y}.png?access_token=pk.eyJ1Ijoic3JuaWFrIiwiYSI6ImNqb3UybW95ejBiM2MzcHM1dzV2YmJxdTAifQ.mVE4Vn_nAB72-rdboaksHA', {
            attribution: 'Map data &copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a> contributors, <a href="https://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, Imagery © <a href="https://www.mapbox.com/">Mapbox</a>',
            maxZoom: 18,
            id: 'mapbox.streets',
            accessToken: 'your.mapbox.access.token'
        }).addTo(mymap);
        L.control.scale().addTo(mymap);
        
        // var marker = L.marker([51.5, -0.09]).addTo(mymap);

        // var circle = L.circle([51.508, -0.11], {
        //     color: 'red',
        //     fillColor: '#f03',
        //     fillOpacity: 0.5,
        //     radius: 500
        // }).addTo(mymap);

        // var polygon = L.polygon([
        //     [51.509, -0.08],
        //     [51.503, -0.06],
        //     [51.51, -0.047]
        // ]).addTo(mymap);

        // marker.bindPopup("<b>Hello world!</b><br>I am a popup.").openPopup();
        // circle.bindPopup("I am a circle.");
        // polygon.bindPopup("I am a polygon.");
        const diameterSafeZone = 3000;
        function whenClicked(e) {
                    // e = event
                    const id = e.target.feature.properties.id;
            console.log(id);
            getFloods(id);
            getHospitals(id, diameterSafeZone);
        // You can make your ajax call declaration here
        //$.ajax(... 
        }
        let i = 0;

        function getFloods(cityId) {
            console.log('getting floods for ' + cityId);
            $.get("api/floods/" + cityId, function(data) {
                console.log( data );
                let coords = [];
                data.map((el) => {
                    // L.marker([parseFloat(el.latitude), parseFloat(el.longtitude)]).addTo(mymap);
                    let geoJson = JSON.parse(el.st_asgeojson); 
                    let prob = el.prob.toString();
                    console.log(prob);
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
                    }).addTo(mymap);

                    // if (prob == 'High') {
                    //     L.circle([geoJson.coordinates[1], geoJson.coordinates[0]], {
                    //         color: 'red',
                    //         fillColor: '#f03',
                    //         fillOpacity: 0.5,
                    //         radius: diameterSafeZone
                    //     }).addTo(mymap);
                    // }

                });

            });
        }

        function getHospitals($id, $safeZoneDiameter) {
            $.get("api/hospitals/" + $id + '?safeZoneDiameter=' + $safeZoneDiameter, function(data) {
                console.log( data );
                data.map((el) => {
                    let geoJsonCities = JSON.parse(el.hospital);
                    let endangered = el.endangered;
                    L.geoJson(geoJsonCities, { style: {color: endangered == null ? 'green' : 'red'}}).addTo(mymap);
                })
            });
        }
        function onEachFeature(feature, layer) {
            layer.feature.properties.id = id;
            console.log('##feat', layer.feature.properties.id);
            layer.on({
                click: whenClicked
            });
        }

        // $('#testBtn').click(function() {
        //     $.get("api/hospitals/" + , function(data) {
        //         console.log( data );
        //         data.map((el) => {
        //             let geoJsonCities = JSON.parse(el.st_asgeojson);
        //             L.geoJson(geoJsonCities).addTo(mymap);
        //         })
        //     });
        // });

         $('#floodsBtn').click(function() {
            $.get("api/floods", function(data) {
                console.log( data );
                let coords = [];
                data.map((el) => {
                    // L.marker([parseFloat(el.latitude), parseFloat(el.longtitude)]).addTo(mymap);
                    let geoJson = JSON.parse(el.st_asgeojson); 
                    L.geoJson(geoJson).addTo(mymap);
                });

            });
        });
        let id;
        $('#citiesBtn').click(function() {  
            $.get("api/cities", function(data) {
                // console.log( data );
                // console.log(JSON.parse(data[0].st_asgeojson));
                data.map((el) => {
                    // let parsed = JSON.parse(el);
                    let geoJson = JSON.parse(el.st_asgeojson);
                    id = JSON.parse(el.id).toString();
                    // console.log(geoJson);
                    L.geoJson(geoJson, {onEachFeature: onEachFeature}).addTo(mymap);
                    // console.log(ele);
                    // L.polygon(L.geoJSON().coordsToLatLng(JSON.parse(el.st_asgeojson).coordinates)).addTo(mymap);
                    // return JSON.parse(el.st_asgeojson).coordinates;
                });
                // console.log(coord);
                
                // let coords = [];
                // data.map((el) => {
                //     L.marker([parseFloat(el.latitude), parseFloat(el.longtitude)]).addTo(mymap);
                //     coords.push([parseFloat(el.latitude), parseFloat(el.longtitude)]);
                // });
                // console.log(coords);
            });
        });
    });
    
       
    </script>
</html>
