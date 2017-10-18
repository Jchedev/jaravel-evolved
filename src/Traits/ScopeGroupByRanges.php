<?php
/**
 * Created by PhpStorm.
 * User: jeanfrancoischedeville
 * Date: 18/09/2017
 * Time: 17:51
 */

namespace Jchedev\Laravel\Traits;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

trait ScopeGroupByRanges
{
    /**
     * @param $query
     * @param $range
     * @param string $date_column
     * @param string $key
     * @return mixed
     */
    public function scopeGroupByRanges($query, $range, $date_column = 'created_at', $key = 'groupement')
    {
        $nb_seconds = time_duration($range);

        $query->select(DB::raw('MAX(id) as id'));

        $query->addSelect(DB::raw("concat(date(" . $date_column . ") , ' ', sec_to_time(time_to_sec(" . $date_column . ")- time_to_sec(" . $date_column . ") % (" . $nb_seconds . ") + (" . $nb_seconds . "))) as " . $key));

        $query->groupBy($key);

        $query->orderBy($key, 'DESC');

        $results = $query->get()->reverse();

        $real_collection = self::query()->whereIn('id', $results->modelKeys())->get()->keyBy('id');

        foreach ($results as $result) {
            $final_attributes = array_merge(
                $real_collection[$result->id]->attributes,
                $result->attributes,
                [
                    $key => Carbon::parse($result->$key)
                ]
            );

            $result->fill($final_attributes)->syncOriginal();
        }

        return $results;
    }
}