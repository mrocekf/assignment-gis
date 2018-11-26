<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MapsController extends Controller
{
    public function portsOfLanding()
    {
        // $ports = DB::select('select * from floods limit 1000');
        // $ports = DB::select('select * from floods where prob = ? ORDER BY fid desc LIMIT 1000', ['High']);
        $ports = DB::select('select latitude, longtitude from fleet_landings LIMIT 1000');
        return response()->json($ports);
    }

    public function floods()
    {
        // $ports = DB::select('select * from floods limit 1000');
        // $ports = DB::select('select * from floods where prob = ? ORDER BY fid desc LIMIT 1000', ['High']);
        // $floods = DB::select('select latitude, longtitude from floods WHERE prob = ? ORDER BY fid LIMIT 500', ['High']);
        // $floods = DB::select('select ST_AsGeoJSON(geom) from relevant_floods WHERE prob = ? ORDER BY fid LIMIT 500', ['High']);
        $floods = DB::select('select ST_AsGeoJSON(geom) from minimal_floods');
        return response()->json($floods);
    }

    public function cities() {
        // $cities = DB::select('select ST_AsGeoJSON(ST_Transform(way, 4326)) from planet_osm_polygon where place = ?', ['town']);
        $cities = DB::select('SELECT DISTINCT ST_AsGeoJSON(converted_way) from cities_polygon');
        return response()->json($cities);
    }

    public function test() {
        // $test = DB::select('select ST_AsGeoJSON(ST_MakePoint(?, ?))', ['50.996535', '-2.583975']);
        // $test = DB::select('SELECT DISTINCT ST_AsGeoJSON(grid.converted_way) AS cities, ST_AsGeoJSON(pts.geom) AS floods FROM minimal_floods AS pts, cities_polygon grid WHERE ST_Contains(grid.converted_way, pts.geom) LIMIT 20');
        $test = DB::select('SELECT ST_AsGeoJSON(pts.city_way) AS cities FROM medium_floods_cities AS pts LIMIT 100');
        return response()->json($test);
    }
}