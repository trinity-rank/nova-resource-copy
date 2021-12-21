<?php

namespace Trinityrank\LaravelNovaResourceCopy;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;

class NovaResourceCopy extends Action
{
    use InteractsWithQueue, Queueable;

    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public $name = 'Copy Resource';
    
    public function handle(ActionFields $fields, Collection $models)
    {
        $table_name = $models->first()->getTable();

        $models->each(function ($model) use ($table_name) {
            $newModel = $model->replicate();

            //default columns
            if ($newModel->status) {
            $newModel->status = false;
            }
            if ($newModel->slug) {
                $array_of_slugs = DB::table($table_name)->pluck('slug');

                if ( Str::contains($model->slug, ['-copy-']) ) {
                    $model_slugs = $array_of_slugs->filter(function ($value, $key) use ($model) {
                        return  (string)Str::of($model->slug)->before('-copy-') 
                        ===     (string)Str::of($value)->before('-copy-');
                    });

                    $biggest_numbers = $model_slugs->map(function ($item, $key) {
                       return (string)Str::of($item)->after('-copy-');
                    })->toArray();

                    $int_biggest_numbers = array_map('intval', $biggest_numbers);
                    $biggest_number = max($int_biggest_numbers);
                    $newModel->slug = Str::of($model->slug)->before('-copy-') . '-copy-' . $biggest_number + 1;

                } else {
                    $model_slugs = $array_of_slugs->filter(function ($value, $key) use ($model) {
                        return  (string)Str::of($model->slug)->before('-copy-') 
                        ===     (string)Str::of($value)->before('-copy-');
                    });

                    $biggest_numbers = $model_slugs->map(function ($item, $key) {
                       return (string)Str::of($item)->after('-copy-');
                    })->toArray();

                    $int_biggest_numbers = array_map('intval', $biggest_numbers);
                    $biggest_number = max($int_biggest_numbers);
                    $newModel->slug = $model_slugs->count() > 1 
                                      ? Str::of($model->slug)->before('-copy-') . '-copy-' . $biggest_number + 1
                                      : $model->slug . '-copy-' . 1;
                }
            }

            //enter columns
            if ( isset($this->data[0]) ) {
                collect($this->data[0])->each(function ($item) use ($newModel, $model) {
                    if ($newModel->$item && !in_array($item, ['slug', 'status'])) {
                        $newModel->$item = $model->$item . " - Copy";
                    }
                });
            }

            //saving copy
            $newModel->save();

            //relationships
            if ( isset($this->data[1]) ) { 

                collect($this->data[1])->each(function ($item) use ($newModel, $model) {

                    //relationship pivot:
                    if ( !Schema::hasColumn($item[0], $item[1] . '_type') ){
                        $rowData = DB::table($item[0])->where([
                            [ $item[1] . '_id', '=', $model->id ],
                        ])->first();

                        unset($rowData->id);
                        $x = $item[1] . '_id';
                        $rowData->$x  = $newModel->id;

                        $record = json_decode(json_encode($rowData), true);

                        DB::table($item[0])->insert($record);
                    }

                    //relationship polymorphic:
                    elseif ( Schema::hasColumn($item[0], $item[1] . '_id') 
                        &&  DB::table($item[0])->where([
                                [ $item[1] . '_id', '=', $model->id ],
                                [ $item[1] . '_type', '=', get_class($model)],
                            ])->exists()                                     
                        ){

                        $rowData = DB::table($item[0])->where([
                            [ $item[1] . '_id', '=', $model->id ],
                            [ $item[1] . '_type', '=', get_class($model) ],
                        ])->first();

                        unset($rowData->id);
                        $x = $item[1] . '_id';
                        $rowData->$x  = $newModel->id;

                        $record = json_decode(json_encode($rowData), true);

                        DB::table($item[0])->insert($record);
                    }
                });
            }
        });
        
        return Action::message("Selected resources are copied");
    }

    public function fields()
    {
        return [];
    }
}