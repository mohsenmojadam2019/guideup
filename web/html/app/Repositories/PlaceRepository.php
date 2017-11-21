<?php

namespace App\Repositories;

use App\Repositories\Repository;
use App\Models\Place;
use App\Models\Gallery;
use DB;

class PlaceRepository extends Repository {
    
    use \App\Traits\CoordinateTrait;

    protected $modelClass = Place::class;

    public function findByID($id, $relations = null, $fail = true) {
        $query = $this->newQuery();

        $placeRelations = [
            'galleries',
            'reviews',
			'city' => function($query) {
				$query->select(['id', 'name', 'state_id', 'country_id']);
			},
			'state' => function($query) {
				$query->select(['id', 'name']);
			}, 
			'country' => function($query) {
				$query->select(['id', 'name']);
			}
        ];
        
        if($relations != null) {
            $placeRelations = array_merge($placeRelations, $relations);
        }
        
        $query = $query->with($placeRelations);

        if ($fail) {
            $result = $query->findOrFail($id);
        }
        $result = $query->find($id);

        return $result;
    }

    public function search(array $array = []) {
        $paginate = true;
        $orderBy = 'name';

        $places = $this->newQuery();
		
		$places = $places->with([
			'city' => function($query) {
				$query->select(['id', 'name', 'state_id', 'country_id']);
			},
			'state' => function($query) {
				$query->select(['id', 'name']);
			}, 
			'country' => function($query) {
				$query->select(['id', 'name']);
		}]);
				
        if(isset($array['term'])) {
            $places = $places->where('name','like','%'.$array['term'].'%');
        }
        if(isset($array['type'])) {
            $places = $places->where('type','=',$array['type']);
        }
        if(isset($array['city_id'])) {
            $places = $places->where('city_id','=',$array['city_id']);
        }
        if(isset($array['state_id'])) {
            $places = $places->where('state_id','=',$array['state_id']);
        }
        if(isset($array['country_id'])) {
            $places = $places->where('country_id','=',$array['country_id']);
        }
        if(isset($array['latitude']) && isset($array['longitude'])) {
            
            $latitude = $array['latitude'];
            $longitude = $array['longitude'];
            $places->select([
                '*',
                DB::raw("ACOS(SIN(latitude*$this->earthScaleFactor)*SIN($latitude*$this->earthScaleFactor) + COS(latitude*$this->earthScaleFactor)*COS($latitude*$this->earthScaleFactor)*COS((longitude-$longitude)*$this->earthScaleFactor)) * $this->earthRadiusKm as distance")
            ]);
            $orderBy = 'distance';
            
            if(isset($array['distance'])) {
                $places = $places->where(DB::raw("ACOS(SIN(latitude*$this->earthScaleFactor)*SIN($latitude*$this->earthScaleFactor) + COS(latitude*$this->earthScaleFactor)*COS($latitude*$this->earthScaleFactor)*COS((longitude-$longitude)*$this->earthScaleFactor)) * $this->earthRadiusKm"), '<=', $array['distance']);
            }
        }

        if(isset($array['guide_id'])) {
            $places = $places->whereHas('guides', function($query) use ($array) {
                $query->where('id', '=',$array['guide_id']);
            });
        }

        if(isset($array['user_id'])) {
            $places = $places->whereHas('users', function($query) use ($array) {
                $query->where('user_id', '=',$array['user_id']);
            });
        }

        $take = 15;
        if(isset($array['total'])) {
            $take = $array['total'];
        }
        
        if(isset($array['count'])) {
            return ["total" => $places->count()];
        }

        if($orderBy != null && trim($orderBy) != "") {
            $places = $places->orderBy($orderBy);
        }

        if(isset($array['all'])) {
            $paginate = false;
            $take = -1;
        }

        return $this->doQuery($places, $take, $paginate);
    }

    public function create(array $data = []) {
        DB::beginTransaction();
        try {
			if(isset($data['galleries'])) {
				$gallery = $data['galleries'];
				unset($data['galleries']);
			}

            $model = $this->factory($data);
            $this->save($model);
			if(isset($gallery)) {
				$this->saveGallery($model, $gallery);
			}
            DB::commit();
            return $model;
        } catch(Exception $ex) {
            DB::rollBack();
            throw $ex;
        }
    }

    public function update($id, array $data = []) {
        DB::beginTransaction();
        try {
			if(isset($data['galleries'])) {
				$gallery = $data['galleries'];
				unset($data['galleries']);
			}
            
            $model = $this->findByID($id);
            $this->setModelData($model, $data);
            $this->save($model);
			if(isset($gallery)) {
				$this->saveGallery($model, $gallery);
			}
            DB::commit();
            return $model;
        } catch(\Exception $ex) {
            DB::rollBack();
            throw $ex;
        }
    }

    private function saveGallery($model, $gallery) {
        if(isset($gallery)) {
            if(is_array($gallery)) {
                $gallery = array_values($gallery);
            } else {
                $gallery = explode(',', $gallery);
            }
            foreach($gallery as $value) {
                $gallery = Gallery::find($value);
                $gallery->place_id = $model->id;
                $gallery->save();
            }
        }
    }
}