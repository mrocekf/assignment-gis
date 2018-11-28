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
            . 'SELECT DISTINCT ST_AsGeoJSON(h.converted_way) AS hospital, ST_DWithin(f.flood_point::geography, h.converted_way::geography, ?) AS endangered, h.id AS hospital_id '
            .'FROM tmp_floods f RIGHT JOIN hospitals_polygon h ON ST_DWithin(f.flood_point::geography, h.converted_way::geography, ?)'
            .'JOIN cities_polygon c ON ST_Contains(c.converted_way, h.converted_way) '
            .'WHERE c.id = ?;',
            [$cityId, 'High', $diameter, $diameter, $cityId]
        );
        return response()->json($hospitals);
    }

    public function cities() {
        $limit = request()->limit;
        $offset = request()->offset; 
        $cities = DB::select('SELECT ST_AsGeoJSON(converted_way), id, name FROM cities_polygon LIMIT ? OFFSET ?', [$limit, $offset]);
        return response()->json($cities);
    }

    public function floodsForHospital() {
        $hospitalId = request()->hospitalId;
        $diameter = request()->safeZoneDiameter;
        $floods = DB::select(
            'SELECT DISTINCT f.id as f_id, ST_AsGeoJSON(f.geom) AS flood_point_json '
            .'FROM floods f JOIN hospitals_polygon h ON ST_DWithin(f.geom::geography, h.converted_way::geography, ?) '
            .'WHERE h.id = ? AND f.prob = ?',
            [$diameter, $hospitalId, 'High']
        );
        return response()->json($floods);
    }

    public function closestSafeHospital() {
        $cityId = request()->cityId;
        $diameter = request()->safeZoneDiameter;
        $hospitalId = request()->hospitalId;
        $hospital = DB::select(
            'WITH tmp_floods AS '
                .'(SELECT DISTINCT f.geom AS flood_point, f.prob, f.id FROM floods as f JOIN cities_polygon as c ON ST_Contains(c.converted_way, f.geom) WHERE c.id = ? AND f.prob = ?), '
                .'tmp_hospitals AS '
                .'(SELECT DISTINCT h.osm_id, h.converted_way, h.id FROM hospitals_polygon h JOIN cities_polygon c ON ST_Contains(c.converted_way, h.converted_way) WHERE c.id = ?), '
                .'endangered_hospitals AS '
                .'(SELECT DISTINCT h.id FROM tmp_floods f, tmp_hospitals h WHERE ST_DWithin(f.flood_point::geography, h.converted_way::geography, ?)), '
                .'the_hospital AS '
	            .'(SELECT converted_way FROM tmp_hospitals WHERE id = ?) '																				  
            .'SELECT h.osm_id, ST_AsGeoJSON(ST_Centroid(h.converted_way)) AS closest_hospital, ST_AsGeoJSON(ST_Centroid(t.converted_way)) AS endangered_hospital, ST_Distance(h.converted_way::geography, t.converted_way::geography) AS distance '
            .'FROM tmp_hospitals h, the_hospital t '
            .'WHERE h.id NOT IN (SELECT id FROM endangered_hospitals) '
            .'ORDER BY distance LIMIT 1',
            [$cityId, 'High', $cityId, $diameter, $hospitalId]
        );
        return response()->json($hospital);
    }
}