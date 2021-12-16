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

    public $name = 'Copy Row';
    
    public function handle(ActionFields $fields, Collection $models)
    {
        $table_name = $models->first()->getTable();

        foreach ($models as $model) {
            $newModel = $model->replicate();

            //default columns
            if ($newModel->status) {
            $newModel->status = false;
            }
            if ($newModel->title) {
            $newModel->title = $model->title . " copy";
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

                    $biggest_number = max($biggest_numbers);
                    $newModel->slug = Str::of($model->slug)->before('-copy-') . '-copy-' . (int)($biggest_number) + 1;

                } else {
                    $newModel->slug = $model->slug . '-copy-' . 1;
                }
            }

            //enter columns
            if ( isset($this->data['copy_columns']) ) {
                collect($this->data['copy_columns'])->each(function ($item) use ($newModel, $model) {
                    if ($newModel->$item && !in_array($item, ['slug', 'status', 'title'])) {
                        $newModel->$item = $model->$item . " copy";
                    }
                });
            }

            $newModel->save();

            //relationships (polymorphic):
            if ( isset($this->data['relation_tables']) ) {

                collect($this->data['relation_tables'])->each(function ($item) use ($newModel, $model) {

                    if ( Schema::hasColumn($item['table_name'], $item['foreign_key_name'] . '_id') 
                        &&  DB::table($item['table_name'])->where([
                                [ $item['foreign_key_name'] . '_id', '=', $model->id ],
                                [ $item['foreign_key_name'] . '_type', '=', $model->type ],
                            ])->exists()                                     
                        ){

                        $rowData = DB::table($item['table_name'])->where([
                            [ $item['foreign_key_name'] . '_id', '=', $model->id ],
                            [ $item['foreign_key_name'] . '_type', '=', $model->type ],
                        ])->first();

                        unset($rowData->id);
                        $x = $item['foreign_key_name'] . '_id';
                        $rowData->$x  = $newModel->id;

                        $record = json_decode(json_encode($rowData), true);

                        DB::table($item['table_name'])->insert($record );

                    }

                });
            }

        }

        return Action::message("Selected rows are copied");
    }

    public function fields()
    {
        return [];
    }
}