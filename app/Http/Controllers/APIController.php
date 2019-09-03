<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Lumen\Routing\Controller as BaseController;

const DATA_BEGIN_DATE = '2018/10/01';
const DATE_FORMAT = 'Y-m-d';
const ALL_STATIONS = 'all';
const API_HEADERS = [
  'Access-Control-Allow-Origin' => '*',
  'Cache-Control' => 'public, max-age=31536000',
  'X-Powered-By' => 'The Weatherman'
];

class APIController extends BaseController
{
  public function sites()
  {
    $sites = Cache::rememberForever('sites', function () {
      return DB::table('snotel_sites')->get();
    });

    return response()->json($sites, 200, API_HEADERS);
  }

  public function measurements($stationId, $startDate, $count)
  {
    // parameter validation
    $stationId = $stationId ?? ALL_STATIONS;
    $count = (!$count || !is_numeric($count)) ? 10 : $count;
    $startDate = date_create_from_format(DATE_FORMAT, $startDate);

    if (!$startDate) {
        $startDate = date_create(DATA_BEGIN_DATE);
    }

    $startDate = $startDate->format(DATE_FORMAT);
    $endDate = date_create($startDate)
    ->add(date_interval_create_from_date_string($count.' days'))
    ->format(DATE_FORMAT);

    // max out at 10 rows at a time if we're pulling all snotel stations
    if (ALL_STATIONS == $stationId && $count > 10) {
        $count = 10;
    }

    $cacheKey = join('_', [$stationId,$startDate,$count]);

    $measurements = Cache::rememberForever($cacheKey, function() use ($startDate, $endDate, $stationId) {
      $queryParams = [$startDate, $endDate];
      $where = '';

      if(ALL_STATIONS != $stationId) {
        array_push($queryParams, $stationId);
        $where = ' and station_id = ?';
      }

      $query = sprintf('
        select measurement_date, station_id, snow_depth
        from snotel_measurements where measurement_date between ? and ?
        %s
        order by measurement_date asc;
      ', $where);

      $data = DB::select($query, $queryParams);
      $json = [];

      // flip to json structure { 'stationid': [{ date: date, val: val }] }
      foreach ($data as $row) {
        $json[$row->station_id] = $json[$row->station_id] ?? [];

        array_push($json[$row->station_id], [
          'date' => $row->measurement_date,
          'val' => $row->snow_depth
        ]);
      }

      return (object)$json;
    });

    return response()->json($measurements, 200, API_HEADERS);
  }

  public function geoJSON($date) {
    $date = date_create_from_format(DATE_FORMAT, $date)->format(DATE_FORMAT);

    if (!$date) {
      return response()->json("", 403);
    }

    $cacheKey = "geoJSON_".$date;

    $geoJSON = Cache::rememberForever($cacheKey, function() use ($date) {
      $query = '
        select sm.station_id, sm.snow_depth, ss.station_name, ss.county_name, ss.latitude, ss.longitude
        from snotel_measurements as sm
        inner join snotel_sites as ss on sm.station_id = ss.station_id
        where measurement_date = ?
        order by station_id asc;
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

    return response()->json($geoJSON, 200, API_HEADERS);
  }
}
