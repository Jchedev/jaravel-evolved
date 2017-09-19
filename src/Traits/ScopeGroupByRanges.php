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
     * @param null $limit
     * @param string $key
     * @return mixed
     */
    public function scopeGroupByRanges($query, $range, $limit = null, $key = 'groupement')
    {
        $nb_seconds = time_duration($range);

        $query->select(DB::raw('MAX(id) as id'));

        $query->addSelect(DB::raw("concat(date(created_at) , ' ', sec_to_time(time_to_sec(created_at)- time_to_sec(created_at) % (" . $nb_seconds . ") + (" . $nb_seconds . "))) as " . $key));

        $query->groupBy($key);

        $query->take($limit);

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