# Overview

My application displays regions of England. Most important features are:
- select a region of interest
- show floods probability for given region
- show hospitals in given region with information, wether they are endangered by a flood
- find the closest "safe" (not endangered by flood) hospital from selected hospital
- show route from endangered hospital to safe hospital 
- show basich statistics/info about displayed map-data 



![Screenshot](screenshot_1.png)

The application has 2 separate parts, the client which is a [frontend web application](#frontend) using [leaflet.js](https://leafletjs.com/) and [jQuery](https://jquery.com/). The [backend application](#backend) is written in [Laravel](https://laravel.com/), backed by PostGIS. The frontend application communicates with backend using a [REST API](#api).

# Frontend

The frontend application is part of a Laravel application, and can be found in (`resources/views/welcome.blade.php`). It contains a .blade.php file with HTML code and javascript logic. At first, a user can see map in leaflet library, displaying [mapbox](https://www.mapbox.com/) map layer with England's regions. After clicking a region, map is zoomed to it and further information are retrieved from backend for given region.

After all floods and hospitals are displayed on the map, user can then select an endangered hospital (red ones) to get more detailed info. Particular flood points, which endangere selected hospital, are highlighted. User can also define, what distance from flood still endangeres any hospital. These "endangering circles" are drawn in red.

For given region also the closest safe hospital is found and a route to that safe hospital can be shown (if checkbox is checked). User can change safe zone diameter and then press the button to reload the map. Any time some data are being requested from server, loading screen is shown. When user finishes work with selected region, user can then select another region by clicking on it.

Front end code only displays data from backend ant takes care of data manipulation.

# Backend

The backend application is written in php in Laravel framework. Backend uses postgis and postgreSQL for data storage & manipulation. It only has 4 methods to get data from DB in `app/Http/Controllers/MapsController.php`.

## Database

There are 3 relevant tables in the database. It's `floods`, `cities_polygon` and `hospitals_polygon`, which are taken from `planet_osm_polygon`. I managed to find a suitable OSM data for whole England. The file is large but after some googling it could have been transformet and finally imported to postgis DB.

Information about floods are taken from [kaggle](https://www.kaggle.com/getthedata/open-flood-risk-by-postcode). There are separate points with lat/lon coordinates and flood probability for every point. Taking the fact, that England's region polygons are quite detailed and many, into consideration, I used ST_simplify function to speed up the map loading. All DB queries are written in Laravel's [Eloquent](https://laravel.com/docs/5.7/eloquent) RAW queries. The geometry data from DB is sent to FE using geojson format.

## Api

**Get all regions**

`GET /cities`

**Find floods for region**

`GET /floods/{cityId}`

**Find hospitals for region, returns also information if hospital is in danger of flood**

`GET /hospitals/{cityId}?safeZoneDiameter=[meters]`

**Find floods, which endager selected hospital**

`GET /floods-for-hospital?hospitalId=[id]&safeZoneDiameter=[meters]`

**Find closest hospital, which is not endagered by dangerous ("High" probability) flood**

`GET /closest-safe-hospital?hospitalId=[id]&safeZoneDiameter=[meters]&cityId=[id]`
### Response

API calls return geojson with geometry data, and additional information (such as count, distance, ...) if needed.
