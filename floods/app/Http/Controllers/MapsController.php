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

    public function floods($cityId)
    {
        // $ports = DB::select('select * from floods limit 1000');
        // $ports = DB::select('select * from floods where prob = ? ORDER BY fid desc LIMIT 1000', ['High']);
        // $floods = DB::select('select latitude, longtitude from floods WHERE prob = ? ORDER BY fid LIMIT 500', ['High']);
        // $floods = DB::select('select ST_AsGeoJSON(geom) from relevant_floods WHERE prob = ? ORDER BY fid LIMIT 500', ['High']);
        $floods = DB::select(
            'SELECT ST_AsGeoJSON(geom), prob, osm_id FROM floods as f JOIN cities_polygon as c ON ST_Contains(c.converted_way, f.geom) WHERE c.id = ? AND f.prob != ?',
            [$cityId, 'None']
        );
        return response()->json($floods);
    }

    public function hospitals($cityId) {
        $diameter = request()->safeZoneDiameter;
        // $hospitals = DB::select(
        //     'SELECT DISTINCT ST_AsGeoJSON(h.converted_way) AS hospital, ST_DWithin(flood_point, h.converted_way, ?) AS endangered FROM (SELECT f.geom AS flood_point, f.prob FROM floods as f JOIN cities_polygon as c ON ST_Contains(c.converted_way, f.geom) WHERE c.id = ? AND f.prob != ?) f, hospitals_polygon h ',
        //     [$diameter, $cityId, 'None']
        // );
        $hospitals = DB::select(
            'WITH tmp_floods AS ' 
            .'(SELECT f.geom AS flood_point, f.prob, f.id FROM floods as f JOIN cities_polygon as c ON ST_Contains(c.converted_way, f.geom) WHERE c.id = ? AND f.prob = ?) '
            . 'SELECT DISTINCT ST_AsGeoJSON(h.converted_way) AS hospital, ST_DWithin(f.flood_point, h.converted_way, ?/111139.0) AS endangered, h.id AS hospital_id '
            .'FROM tmp_floods f RIGHT JOIN hospitals_polygon h ON ST_DWithin(f.flood_point, h.converted_way, ?/111139.0)'
            .'JOIN cities_polygon c ON ST_Contains(c.converted_way, h.converted_way) '
            .'WHERE c.id = ?;',
            [$cityId, 'High', $diameter, $diameter, $cityId]
        );
        return response()->json($hospitals);
    }

    public function cities() {
        $limit = request()->limit;
        $offset = request()->offset; 
        $cities = DB::select('SELECT ST_AsGeoJSON(converted_way), id FROM cities_polygon LIMIT ? OFFSET ?', [$limit, $offset]);
        return response()->json($cities);
    }

    public function floodsForHospital() {
        $hospitalId = request()->hospitalId;
        $diameter = request()->safeZoneDiameter;
        $floods = DB::select(
            'SELECT DISTINCT f.id as f_id, ST_AsGeoJSON(f.geom) AS flood_point_json '
            .'FROM floods f JOIN hospitals_polygon h ON ST_DWithin(f.geom, h.converted_way, ?/111139.0) '
            .'WHERE h.id = ? AND f.prob = ?',
            [$diameter, $hospitalId, 'High']
        );
        return response()->json($floods);
    }
}