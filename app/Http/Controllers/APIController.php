<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Lumen\Routing\Controller as BaseController;

class APIController extends BaseController
{
  const DATE_FORMAT = 'Y-m-d';
  const API_HEADERS = [
    'Access-Control-Allow-Origin' => '*',
    'Cache-Control' => 'public, max-age=31536000',
    'X-Powered-By' => 'The Weatherman'
  ];

  public function geoJSON($date) {
    $date = date_create_from_format(self::DATE_FORMAT, $date);

    if (!$date) {
      return response()->json("", 403);
    }

    $date = $date->format(self::DATE_FORMAT);
    $cacheKey = "geoJSON_".$date;

    $geoJSON = Cache::rememberForever($cacheKey, function() use ($date) {
      $query = '
        select sm.station_id, sm.snow_depth, ss.station_name, ss.county_name, ss.latitude, ss.longitude
        from snotel_measurements as sm
        inner join snotel_sites as ss on sm.station_id = ss.station_id
        where measurement_date = ?
        order by ss.station_name asc;
      ';

      $data = DB::select($query, [$date]);
      $json = [
        'type' => 'FeatureCollection',
        'features' => []
      ];

      // flip to geoJSON structure
      // let sitesGeo = {
      //   type: "FeatureCollection",
      //   features: []
      // };
      // type: "Feature",
      // geometry: {
      //   type: "Point",
      //   coordinates: [site.longitude, site.latitude],
      // },
      // properties: {
      //   // title: `${site.station_name}, ${site.county_name} county`,
      //   title: site.station_name,
      //   stationId: site.station_id,
      //   snow: Math.random()*10,
      //   icon: "monument"
      // }

      foreach ($data as $row) {
        array_push($json['features'], [
          'type' => 'Feature',
          'geometry' => (object)[
            'type' => 'Point',
            'coordinates' => [$row->longitude, $row->latitude]
          ],
          'properties' => (object)[
            'stationId' => $row->station_id,
            'name' => $row->station_name,
            'county' => $row->county_name,
            'snow' => $row->snow_depth
          ]
        ]);
      }

      return (object)$json;
    });

    return response()->json($geoJSON, 200, self::API_HEADERS);
  }
}
