<?php

namespace App\Repositories;

use App\Repositories\Repository;
use App\Models\User;
use App\Models\Language;
use App\Models\Address;
use App\Models\SocialLogin;
use DB;
use Exception;

class UserRepository extends Repository {

    protected $guideRepository;
    public function __constructor(GuideRepository $guideRepository) {
        $this->guideRepository = $guideRepository;
    }

    use \App\Traits\StorageTrait;
    
    protected $modelClass = User::class;

    public function findByID($id, $relations = null, $fail = true) {
        $query = $this->newQuery();
        if($relations != null) {
            $query = $query->with($relations);
        }
        if ($fail) {
            return $query->where("id","=",$id)
                ->orWhere("email","=",$id)
                ->firstOrFail();
        }
        return $query->where("id","=",$id)
                ->orWhere("email","=",$id)
                ->first();
    }

	public function search(array $array = []) {
        $paginate = true;
        $orderBy = 'name';

        $users = $this->newQuery();
		
		$users = $users->with([
			'languages' => function($query) {
				$query->select(['id', 'name', 'user_id']);
			},
			'address.city' => function($query) {
				$query->select(['id', 'name', 'state_id', 'country_id']);
			},
			'address.city.state' => function($query) {
				$query->select(['id', 'name']);
			}, 
			'address.city.country' => function($query) {
				$query->select(['id', 'name']);
		}]);
				
        if(isset($array['term'])) {
            $users = $users->where('name','like','%'.$array['term'].'%')
							 ->orWhere('email', 'like','%'.$array['term'].'%');
        }
		
        if(isset($array['type'])) {
			if($array['type'] === 'guide') {
				$users = $users->whereHas('guide');
			}
			else if($array['type'] === 'admin') {
				$users = $users->where('is_admin','=','1');
			}
			else if($array['type'] === 'male') {
				$users = $users->where('gender','=','m');
			}
			else if($array['type'] === 'female') {
				$users = $users->where('gender','=','f');
			}
			else if($array['type'] === 'nogender') {
				$users = $users->whereNull('gender');
			}
			else if($array['type'] === 'active') {
				$users = $users->whereNull('deleted_at');
			}
			else if($array['type'] === 'inactive') {
				$users = $users->whereNotNull('deleted_at');
			}
        }

        $take = 15;
        if(isset($array['total'])) {
            $take = $array['total'];
        }
        
        if(isset($array['count'])) {
            return ["total" => $users->count()];
        }

        if($orderBy != null && trim($orderBy) != "") {
            $users = $users->orderBy($orderBy);
        }

        if(isset($array['all'])) {
            $paginate = false;
            $take = -1;
        }

        return $this->doQuery($users, $take, $paginate);
    }
	
    public function create(array $data = []) {
        if(isset($data['place'])) {
            $place = $data['place'];
            unset($data['place']);
        }
        else if(isset($data['places'])) {
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
		
		if(isset($data['born']) && strtotime($data['born']) <= strtotime('1900-01-01')) {
			unset($data['born']);
		}
		if(!isset($data['is_admin'])) {
			$data['is_admin'] = 0;
		}

        $model = $this->factory($data);
        $model->password = bcrypt($model->password);
        DB::beginTransaction();
        try {            
            if(isset($address)) {
                if(is_string($address))
                {
                    $address = json_decode($address, true);
                }
                $this->saveAddress($model, $address);
            }
            $this->save($model);

        if(isset($languages)) {
            $this->saveLanguages($model, $languages);
        }
        
        if(isset($place)) {
            $model->places()->toogle([$place]);
        }
        else if(isset($places)) {
            $this->savePlaces($model, $places);
        }
            DB::commit();
            return $model;
        } catch(Exception $ex) {
            DB::rollBack();
            throw $ex;
        }
    }    

    public function update($id, array $data = []) {
        if(isset($data['place'])) {
            $place = $data['place'];
            unset($data['place']);
        }
        else if(isset($data['places'])) {
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
		
		if(isset($data['born']) && strtotime($data['born']) <= strtotime('1900-01-01')) {
			unset($data['born']);
		}
if(isset($data['password'])) {
$data['password'] = bcrypt($data['password']);
}
        
        DB::beginTransaction();
        try {            
            $model = $this->findByID($id);
            
            if (isset($data['file'])) {
                $data['avatar'] = $this->saveAvatar($data['file'], $model->avatar);
                unset($data['file']);
            }
			
            $this->setModelData($model, $data);
            
            if(isset($address)) {
                if(is_string($address))
                {
                    $address = json_decode($address, true);
                }
                $this->saveAddress($model, $address);
            }
            
            $this->save($model);
            
            if(isset($languages)) {
                $this->saveLanguages($model, $languages);
            }

            if(isset($place)) {
                $model->places()->toggle([$place]);
            } 
            else if(isset($places)) {
                $this->savePlaces($model, $places);
            }
            DB::commit();
            return $model;
        } catch(\Exception $ex) {
            DB::rollBack();
            throw $ex;
        }
    }

    public function updateSocialLogin($id, array $data = []) {
        DB::beginTransaction();
        try {            
            $model = $this->findByID($id);
            $model->socialLogins()->where('social_id','=', $data['social_id'])->delete();
            $model->socialLogins()->save(new SocialLogin($data));
            DB::commit();
            return $model;
        } catch(\Exception $ex) {
            DB::rollBack();
            throw $ex;
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
                $language->name = trim($value);
                $model->languages()->save($language);
            }
        }
    }

    private function saveAddress($model, $address) {
        if(isset($address)) {
			if(!(isset($address['street']) && isset($address['city_id']))) {
				return;
			}
            $address = collect($address)->only(['id','street', 'number', 'district', 'city_id', 'postal_code']);
			
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

    private function savePlaces($model, $places) {
        if(isset($places)) {
            if(is_array($places)) {
                $places = array_values($places);
            } else {
                $places = explode(',', $places);
            }
            
            $model->places()->sync($places);
        }
    }

    /*protected function createChatUser($user) {
        //Create new User
        //return $user;
        if($user != null) {
            $user->chat_username = str_replace('@','[at]',$user->email);
            $user->chat_password = str_random(12);
            $url = "http://localhost:5285/api/admin";
            $host = "guideup.com.br";
            
            $options = array(
                CURLOPT_RETURNTRANSFER => true,   // return web page
                CURLOPT_HEADER         => false,  // don't return headers
                CURLOPT_FOLLOWLOCATION => true,   // follow redirects
                CURLOPT_MAXREDIRS      => 10,     // stop after 10 redirects
                CURLOPT_ENCODING       => "",     // handle compressed
                CURLOPT_POST      		=> "true", // set post header
                CURLOPT_AUTOREFERER    => true,   // set referrer on redirect
                CURLOPT_CONNECTTIMEOUT => 120,    // time-out on connect
                CURLOPT_TIMEOUT        => 120,    // time-out on response
                CURLOPT_HTTPHEADER	   => array(  //Set header
                    "Host: $host",
                    "Content-Type: application/json",
                    )
            ); 

            $ch = curl_init($url); 
            curl_setopt_array($ch, $options);
            $data = '{"key":"secret","command":"register","args":["'.$user->chat_username.'","'.$host.'","'.$user->chat_password.'"]}';
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            $content  = curl_exec($ch);
            curl_close($ch);
            if(strrpos($content,"ok") !== false || strrpos($content,"exists") !== false) {
                //Add the user to the shared routed group
                $ch = curl_init($url); 
                curl_setopt_array($ch, $options);
                $data = '{"key":"secret","command":"set_vcard","args":["'.$user->chat_username.'","'.$host.'","FN","'.$user->name.'"]}';
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                $content  = curl_exec($ch);
                curl_close($ch);
                if(strrpos($content,"ok") !== false) {
                    return $user;
                }
            }
        }
        throw new Exception("Error creating chat user");
    }*/
}
