<?php

namespace App\Repositories;

use App\Repositories\Repository;
use App\Models\Guide;
use App\Models\Address;
use App\Models\Language;
use DB;
use Exception;

class GuideRepository extends Repository {
    
    use \App\Traits\CoordinateTrait;
    use \App\Traits\StorageTrait;


    protected $modelClass = Guide::class;

    public function findByID($id, $relations = null, $fail = true) {
        $query = $this->newQuery();

        $guideRelations = ['user' => function($query) {
            $query->select('id', 'name', 'email', 'chat_username');
        }, 
        'languages',
        'address',
        'address.city' => function($query) {
            $query->select(['id', 'name', 'state_id', 'country_id']);
        },
        'address.city.state' => function($query) {
            $query->select(['id', 'name']);
        }, 
        'address.city.country' => function($query) {
            $query->select(['id', 'name']);
        }];
        
        if($relations != null) {
            $guideRelations = array_merge($guideRelations, $relations);
        }
        
        $query = $query->with($guideRelations);

        if ($fail) {
            $result = $query->findOrFail($id);
        }
        $result = $query->find($id);

        if($result->address != null && $result->address->city != null)
        {
            $result->address->state = $result->address->city->state->name;
            $result->address->state_id = $result->address->city->state_id;
            $result->address->country = $result->address->city->country->name;
            $result->address->country_id = $result->address->city->country_id;
            $city = $result->address->city->name;
            unset($result->address->city);
            $result->address->city = $city;
        }
        return $result;
    }
    
    public function findByUserID($user_id, $relations = null, $fail = true) {
        $query = $this->newQuery();

        $guideRelations = ['user' => function($query) {
            $query->select('id', 'name', 'email', 'chat_username');
        }, 
        'languages',
        'address',
        'address.city' => function($query) {
            $query->select(['id', 'name', 'state_id', 'country_id']);
        },
        'address.city.state' => function($query) {
            $query->select(['id', 'name']);
        }, 
        'address.city.country' => function($query) {
            $query->select(['id', 'name']);
        }];
                
        if($relations != null) {
            $guideRelations = array_merge($guideRelations, $relations);
        }
        
        $query = $query->with($guideRelations);

        $query->where('user_id','=', $user_id);
        if ($fail) {
            $result = $query->firstOrFail();
        }
        $result = $query->first();
        
        $result->languages = $result->languages->pluck('name');
        
        if($result->address != null && $result->address->city != null)
        {
            $result->address->state = $result->address->city->state->name;
            $result->address->state_id = $result->address->city->state_id;
            $result->address->country = $result->address->city->country->name;
            $result->address->country_id = $result->address->city->country_id;
            $city = $result->address->city->name;
            unset($result->address->city);
            $result->address->city = $city;
        }
        return $result;
    }

    public function doQuery($query = null, $take = 15, $paginate = true)
    {
        if(is_null($query)) {
            $query = $this->newQuery();
        }
        return parent::doQuery($query, $take, $paginate);
    }
    
    public function search(array $array = [], $collumns = ["*"]) {
        $orderBy = null;
        $guides = $this->newQuery();
        if(isset($array['user_id'])) {
            $guides = $guides->where('user_id', '=', $array['user_id']);
        }
        if(isset($array['city_id'])) {
            $guides = $guides->where('city_id', '=', $array['city_id']);
        }
        if(isset($array['term'])) {
            $guides = $guides->whereHas('user', function($query) use ($array) {
                $query->where('name','like','%'.$array['term'].'%');
            });
        }
        if(isset($array['latitude']) && isset($array['longitude'])) {
            
            $latitude = $array['latitude'];
            $longitude = $array['longitude'];
            array_push($collumns,
                DB::raw("ACOS(SIN(latitude*$this->earthScaleFactor)*SIN($latitude*$this->earthScaleFactor) + COS(latitude*$this->earthScaleFactor)*COS($latitude*$this->earthScaleFactor)*COS((longitude-$longitude)*$this->earthScaleFactor)) * $this->earthRadiusKm as distance")
            );
            $orderBy = 'distance';
            
            if(isset($array['distance'])) {
                $guides = $guides->where(DB::raw("ACOS(SIN(latitude*$this->earthScaleFactor)*SIN($latitude*$this->earthScaleFactor) + COS(latitude*$this->earthScaleFactor)*COS($latitude*$this->earthScaleFactor)*COS((longitude-$longitude)*$this->earthScaleFactor)) * $this->earthRadiusKm"), '<=', $array['distance']);
            }
        }
        
        if(isset($array['place_id'])) {
            $guides = $guides->whereHas('places', function($query) use ($array) {
                $query->where('id', '=',$array['place_id']);
            });
        }

        $take = 15;
        if(isset($array['total'])) {
            $take = $array['total'];
        }
        
        if(isset($array['count'])) {
            return ["total" => $guides->count()];
        }

        if($orderBy != null && trim($orderBy) != "") {
            $guides = $guides->orderBy($orderBy);
        }

        $guides = $guides->with(["user" => function($query) {
            $query->select('id', 'name', 'email');
        },
        'languages']);

        $guides = $guides->select($collumns);

        $results = $this->doQuery($guides, $take);

        return $results;
    }

    public function existsForUser($user_id) {        
        $exist = $this->newQuery()->where('user_id','=',$user_id)->count();
        return ($exist !== false && $exist > 0);
    }
    
    public function create(array $data = []) {
        DB::beginTransaction();
        try {
            if(isset($data['places'])) {
                $places = $data['places'];
                unset($data['places']);
            }

            if(isset($data['address'])) {
                $address = $data['address'];
                unset($data['address']);
            }
            
            if(isset($data['languages'])) {
                $languages = $data['languages'];
                unset($data['languages']);
            }

            if (isset($data['file'])) {
                $data['avatar'] = $this->saveAvatar($data['file'], null);
                unset($data['file']);
            }

            $model = $this->factory($data);

            if(isset($address)) {
                $this->saveAddress($model, $address);
            }

            $this->save($model);

            if(isset($places)) {
                $this->savePlaces($model, $places);
            }
            
            if(isset($languages)) {
                $this->saveLanguages($model, $languages);
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
            if(isset($data['places'])) {
                $places = $data['places'];
                unset($data['places']);
            }
            if(isset($data['address'])) {
                $address = $data['address'];
                unset($data['address']);
            }
            if(isset($data['languages'])) {
                $languages = $data['languages'];
                unset($data['languages']);
            }

            $model = $this->newQuery()->findOrFail($id);
            
            if (isset($data['file'])) {
                $data['avatar'] = $this->saveAvatar($data['file'], $model->avatar);
                unset($data['file']);
            }

            $this->setModelData($model, $data);
            if(isset($address)) {
                if(is_string($address))
                {
                    $address = json_decode($address);
                }
                $this->saveAddress($model, $address);
            }

            $this->save($model);

            if(isset($places)) {
                $this->savePlaces($model, $places);
            }
            if(isset($languages)) {
                $this->saveLanguages($model, $languages);
            }

            DB::commit();
            return $this->findByID($id);
        } catch(\Exception $ex) {
            DB::rollBack();
            throw $ex;
        }
    }

    private function savePlaces($model, $places) {
        if(isset($places)) {
            if(is_array($places)) {
                $places = array_values($places);
            } else {
                $places = explode(',', $places);
            }
            
            //If exists remove, otherwise add
            $model->places()->toggle($places);
        }
    }

    private function saveAddress($model, $address) {
        if(isset($address)) {
            $address = collect($address)->only(['id', 'street', 'number', 'district', 'city_id', 'postal_code']);
            if(!isset($address['id']) || $address['id'] == null)
            {
                unset($address['id']);
                $address = Address::create($address->toArray());
                $model->address()->associate($address);
                return;
            }
            else if($model->address != null)
            {
                if($address['street'] != null)
                {
                    $model->address->street = $address['street'];
                }
                
                if($address['number'] != null)
                {
                    $model->address->number = $address['number'];
                }

                if($address['district'] != null)
                {
                    $model->address->district = $address['district'];
                }
                
                if($address['city_id'] != null)
                {
                    $model->address->city_id = $address['city_id'];
                }
                
                if($address['postal_code'] != null)
                {
                    $model->address->postal_code = $address['postal_code'];
                }
                $model->address->save();
            }
        }
    }

    private function saveLanguages($model, $languages) {
        $model->languages()->delete();

        if(isset($languages)) {
            if(is_array($languages)) {
                $firstElementList = [];
                foreach ($languages as $key => $value) 
                {
                    if(is_array($value))
                    {
                        array_push($firstElementList, array_values($value)[0]);
                        continue;
                    }
                    array_push($firstElementList, $value);
                }
                $languages = $firstElementList;
            } else {
                $languages = explode(',', $languages);
            }
            
            foreach($languages as $value) {
                $language = new Language();
                $language->name = $value;
                $model->languages()->save($language);
            }
        }
    }
}