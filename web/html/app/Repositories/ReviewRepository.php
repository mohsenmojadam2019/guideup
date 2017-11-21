<?php

namespace App\Repositories;

use App\Repositories\Repository;
use App\Models\Review;
use DB;
use Exception;

class ReviewRepository extends Repository {

    protected $modelClass = Review::class;

    public function search(array $array = []) {
        $reviews = $this->newQuery();
        if(isset($array['user_id'])) {
            $reviews = $reviews->where('user_id', '=', $array['user_id']);
        }
        if(isset($array['place_id'])) {
            $reviews = $reviews->where('place_id', '=', $array['place_id']);
        }
        if(isset($array['guide_id'])) {
            $reviews = $reviews->where('guide_id', '=', $array['guide_id']);
        }

        $take = 15;
        if(isset($array['total'])) {
            $take = $array['total'];
        }
        
        if(isset($array['count'])) {
            return ["total" => $reviews->count()];
        }
        
        $reviews = $reviews->orderBy('id')->orderBy('updated_at')->orderBy('created_at');

        return $this->doQuery($reviews, $take);
    }
    
    public function create(array $data = []) {
        $model = $this->factory($data);

        if($model->place_id == null && $model->guide_id == null) {
            throw new \Exception('Inform a Guide or a Place');
        } 
        
        if(!($model->place_id > 0 || $model->guide_id > 0)) {
            throw new \Exception('Inform only Guide or only Place');
        }
        
        $query = $this->newQuery()->where('user_id','=',$model->user_id);
        if($model->place_id > 0) {
            unset($model->guide_id);
            $query = $query->where('place_id','=', $model->place_id);
        } 
        else if($model->guide_id > 0) {
            unset($model->place_id);
            $query = $query->where('guide_id','=', $model->guide_id);
        }

        if($query->count() > 0) {
                throw new \Exception('User already reviewed');
        }

        $this->save($model);
        return $model;
    }
    
}