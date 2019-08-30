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

class APIController extends BaseController
{
    public function sites()
    {
        $sites = Cache::rememberForever('sites', function () {
            return DB::table('snotel_sites')->get();
        });

        return response()->json($sites);
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
            $query = sprintf('
                select measurement_date, station_id, snow_depth
                from snotel_measurements where measurement_date between ? and ?
                %s
                order by measurement_date asc;
            ', (ALL_STATIONS != $stationId) ? ' and station_id = ?' : '');

            $data = DB::select($query, [$startDate, $endDate, $stationId]);
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

        return response()->json($measurements);
    }
}
