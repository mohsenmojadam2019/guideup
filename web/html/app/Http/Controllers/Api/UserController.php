<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;

use Response;
use Socialite;

use App\Http\Controllers\Controller;
use App\Http\Requests;
use App\Http\Requests\UserRequest;

use App\Models\User;
use App\Repositories\UserRepository;
use App\Repositories\PlaceRepository;
use App\Repositories\ReviewRepository;
use App\Repositories\GuideRepository;
use App\Repositories\GalleryRepository;

class UserController extends Controller
{
    protected $users;
    protected $places;
    protected $reviews;
    protected $guides;
    protected $galleries;

    protected $fields = ['email', 'name', 'password', 'file', 'born', 'gender', 'phone', 'languages', 'address', 'place', 'places', 'is_admin'];

    public function __construct(UserRepository $userRepository,
                                PlaceRepository $placeRepository,
                                ReviewRepository $reviewRepository,
                                GuideRepository $guideRepository,
                                GalleryRepository $galleryRepository) {
        $this->users = $userRepository;
        $this->places = $placeRepository;
        $this->reviews = $reviewRepository;
        $this->guides = $guideRepository;
        $this->galleries = $galleryRepository;

        $this->middleware('auth:api', ['except' => ['store', 'getOrCreateUser', 'exists']]);
    }

    private function formatUser($user) {
        if($user == null) return null;

        $user->languages = $user->languages->pluck('name');
        $user->address = $user->address()->with(['city' => function($query) {
            $query->select(['id', 'name', 'state_id', 'country_id']);
        },
        'city.state' => function($query) {
            $query->select(['id', 'name']);
        }, 
        'city.country' => function($query) {
            $query->select(['id', 'name']);
        }])->first();

        if($user->address != null && $user->address->city != null)
        {
            $user->address->state = $user->address->city->state->name;
            $user->address->state_id = $user->address->city->state_id;
            $user->address->country = $user->address->city->country->name;
            $user->address->country_id = $user->address->city->country_id;
            $city = $user->address->city->name;
            unset($user->address->city);
            $user->address->city = $city;
        }
		
		$guard = Auth::guard('api');
		$is_admin = ($guard->check() && $guard->user()->is_admin == 1);
		if($is_admin) {
			$user->makeVisible('is_admin');
		}

        return $user;
    }
    
    public function index(Request $request) {
        try {   
			$is_admin = (Auth::check() && Auth::user()->is_admin == 1);
            if(!$is_admin) {
                return Response::json(['error' => [
                    'name' => 'not_authorized',
                    'message' => 'Not Authorized' 
                    ]
                ], 403);
            }
			
			$users = $this->users->search($request->only(['term', 'type', 'total', 'count', 'all']));
			foreach($users as $user) {
				$user->makeVisible('is_admin');
			}

            return Response::json($users,200);
        }
        catch (ModelNotFoundException $ex) {
                return Response::json(['error' => [
                    'name' => 'guide_not_found',
                    'message' => 'Guide not found' 
                    ]
                ], 404);
        }
        catch(\Exception $e) {
            return Response::json(['error' => [
				'name' => 'user_show_error',
				'message' => $e->getMessage()
				]
			],500);
        }
    }

    public function show($id) {
        try {   
            $user = $this->users->findById($id);
            $user = $this->formatUser($user);
			
            return Response::json($user,200);
        }
        catch (ModelNotFoundException $ex) {
                return Response::json(['error' => [
                    'name' => 'user_not_found',
                    'message' => 'User not found' 
                    ]
                ], 404);
        }
        catch(\Exception $e) {
            return Response::json(['error' => [
				'name' => 'user_show_error',
				'message' => $e->getMessage()
				]
			],500);
        }
    }

	public function getOrCreateUser(Request $request) {
		try {
            $values = $request->only(['token', 'expiresDate']);

			if($values['token'] != null && trim($values['token']) != "") {
				$facebookUser = Socialite::driver('facebook')->userFromToken($values['token']);
                //Verifica se já existe um usuário com esse email
                $user = $this->users->findById($facebookUser->email);
                if(!$user || $user == null) {
					//Create new user
					$data = [];
					$data['name'] = $facebookUser->name;
					$data['email'] = $facebookUser->email;
                    if($facebookUser->user != null && isset($facebookUser->user['gender'])) {
                        $data['gender'] = $facebookUser->user['gender'];
                    }

                    if($facebookUser->user != null && isset($facebookUser->user['birthday'])) {
                        $data['born'] = $facebookUser->user['birthday'];
                    }

					if(isset($facebookUser->avatar_original) && $facebookUser->avatar_original != null) {
                        $data['file'] = $facebookUser->avatar_original;
					}
                    $user = $this->users->create($data);
                    return Response::json($user, 201)
                        ->header('location', route('user.store').'/'.$user->id);
				}
                else {
                    $this->users->updateSocialLogin($user->id, ['social_id' => $facebookUser->id, 'token' => $values['token'], 'expiresin' => $values['expiresDate']]);
                    return Response::json($user, 200);
                }
			}
			else {
				return Response::json(['error' => [
                        'name' => 'token_error',
                        'message' => 'Invalid token'
                    ]
                ], 403);
			}
		}
		catch(\Exception $e) {
			return Response::json(['error' => [
                    'name' => 'user_social_store_error',
                    'message' => $e->getMessage()
                ]
            ], 500);
		}
	}

    public function store(UserRequest $request) {
        try {			     
            if($this->users->exists(['email' => $request->email])) {
                return Response::json(['error' => [
                    'name' => 'user_store_error',
                    'message' => 'This user with this email already exists' 
                    ]
                ], 403);
            }
			
            $values = $request->only($this->fields);
			
			$is_admin = false;
			if(isset($values['is_admin'])) {
				$guard = Auth::guard('api');
				$is_admin = ($guard->check() && $guard->user()->is_admin == 1);
				if(!$is_admin) {
					unset($values['is_admin']);
				}
				else {
					$values['is_admin'] = ($values['is_admin'] == 'true' ? 1 : 0);
				}
			}

            //Create user
            $user = $this->users->create($values);
			if($is_admin) {
				$user->makeVisible('is_admin');
			}

            return Response::json($user, 201)
                ->header('location', route('user.store').'/'.$user->id);
        }
        catch(Exception $e) {
            return Response::json([
                'error' => [
                    'name' => 'user_store_error',
                    'message' => $e->getMessage(),
                    'stack' => $e->getTraceAsString()
                ]
            ],500);
        } 
    }

    public function update(UserRequest $request, $id) {
        try {
            if(!Auth::user()->can('update', $this->users->findById($id))) {
                return Response::json(['error' => [
                    'name' => 'not_authorized',
                    'message' => 'Not Authorized' 
                    ]
                ], 403);
            }

            $values = array_filter($request->only($this->fields));
			
			if(isset($values['is_admin'])) {
				$is_admin = (Auth::check() && Auth::user()->is_admin == 1);
				if(!$is_admin) {
					unset($values['is_admin']);
				}
				else {
					$values['is_admin'] = ($values['is_admin'] == 'true' ? 1 : 0);
				}
			}
			
            //Update the user
            $user = $this->users->update($id, $values);

            return Response::json($user, 200);
        }
        catch (ModelNotFoundException $ex) {
                return Response::json(['error' => [
                    'name' => 'user_not_found',
                    'message' => 'User not found' 
                    ]
                ], 404);
        }
        catch(\Exception $e) {
            return Response::json([
                'error' => [
                    'name' => 'user_update_error',
                    'message' => $e->getMessage(),
                    'stack' => $e->getTraceAsString()
                ]
            ],500);
        }
    }
    
    public function token(UserRequest $request, $id) {
        try {
            if(!Auth::user()->can('update', $this->users->findById($id))) {
                return Response::json(['error' => [
                    'name' => 'not_authorized',
                    'message' => 'Not Authorized' 
                    ]
                ], 403);
            }
            
            //Update the user
            $user = $this->users->update($id, ['fcm_token' => $request->token]);

            return Response::json($user, 200);
        }
        catch (ModelNotFoundException $ex) {
                return Response::json(['error' => [
                    'name' => 'user_not_found',
                    'message' => 'User not found' 
                    ]
                ], 404);
        }
        catch(\Exception $e) {
            return Response::json([
                'error' => [
                    'name' => 'user_update_error',
                    'message' => $e->getMessage(),
                    'stack' => $e->getTraceAsString()
                ]
            ],500);
        }
    }

    public function destroy($id) {
        try {
            if(!Auth::user()->can('delete', $this->users->findById($id))) {
                return Response::json(['error' => [
                    'name' => 'not_authorized',
                    'message' => 'Not Authorized' 
                    ]
                ], 403);
            }
			$user = $this->users->findById($id);
            $this->users->delete($user);
            return Response::json(['ok' => true], 200);
        }
        catch (ModelNotFoundException $ex) {
                return Response::json(['error' => [
                    'name' => 'user_not_found',
                    'message' => 'User not found' 
                    ]
                ], 404);
        }
        catch (Exception $e) {
            return Response::json([
                'error' => [
                    'name' => 'user_delete_error',
                    'message' => $e->getMessage()
                ]
            ], 500);
        }
    }

    public function exists($id, $email)  {
        try {
            return Response::json(['ok' => $this->users->exists([['email','like', $email], ['id', '!=', $id]])], 200);
        }
        catch(\Exception $e) {
            return Response::json(['error' => [
                    name => 'user_exists_error',
                    'message' => $e->getMessage()
                ]
            ], 500);
        }
    }

    public function placeIndex(Request $request, $user_id) {
        try {
            $values = $request->only(['page', 'total', 'term', 'city_id', 'state_id', 'country_id', 'latitude', 'longitude', 'distance']);
            $places = $this->places->search(['user_id' => $user_id]);
            return Response::json($places,200);
        }
        catch(\Exception $e) {
            Response::json(['error' => [
				'name' => 'user_place_index_error',
				'message' => $e->getMessage()
				]
			],500);
        }
    }

    public function placeShow($user_id, $place_id) {
        try {
            $place = $this->places->findByID($place_id);
            if($place == null || $place->user_id != $user_id) {
                Response::json(['error' => [
                    'name' => 'place_not_found',
                    'message' => 'Place not found'
                    ]
                ],404);
            }
            return Response::json($place,200);
        }
        catch(\Exception $e) {
            Response::json(['error' => [
				'name' => 'user_place_index_error',
				'message' => $e->getMessage()
				]
			],500);
        }
    }

    public function reviewIndex(Request $request, $user_id) {
        try {
            $values = $request->only(['page', 'total']);
            $reviews = $this->reviews->search(['user_id' => $user_id]);
            return Response::json($reviews,200);
        }
        catch(\Exception $e) {
            Response::json(['error' => [
				'name' => 'user_review_index_error',
				'message' => $e->getMessage()
				]
			],500);
        }
    }

    public function reviewShow($user_id, $review_id) {
        try {
            $review = $this->reviews->findByID($review_id);
            if($review == null || $review->user_id != $user_id) {
                Response::json(['error' => [
                    'name' => 'review_not_found',
                    'message' => 'Review not found'
                    ]
                ],404);
            }
            return Response::json($review,200);
        }
        catch(\Exception $e) {
            Response::json(['error' => [
				'name' => 'user_review_index_error',
				'message' => $e->getMessage()
				]
			],500);
        }
    }
    
    public function guideIndex(Request $request, $user_id) {
        try {
            $guide = $this->guides->findByUserID($user_id);
            return Response::json($guide,200);
        }
        catch (ModelNotFoundException $ex) {
                return Response::json(['error' => [
                    'name' => 'guide_not_found',
                    'message' => 'Guide not found' 
                    ]
                ], 404);
        }
        catch(\Exception $e) {
            Response::json(['error' => [
				'name' => 'user_guide_index_error',
				'message' => $e->getMessage()
				]
			],500);
        }
    }
    
    public function galleryIndex(Request $request, $author_id) {
        try {
            $galleries = $this->galleries->search(['author_id' => $author_id, 'guide_id' => null]);
            return Response::json($galleries,200);
        }
        catch(\Exception $e) {
            return Response::json(['error' => [
				'name' => 'guide_gallery_index_error',
				'message' => $e->getMessage()
				]
			],500);
        }
    }

    public function galleryShow($author_id, $gallery_id) {
        try {
            $gallery = $this->galleries->findByID($gallery_id);
            if($gallery == null || $gallery->author_id != $author_id) {
                return Response::json(['error' => [
                    'name' => 'gallery_not_found',
                    'message' => 'Gallery not found'
                    ]
                ],404);
            }
            return Response::json($gallery,200);
        }
        catch (ModelNotFoundException $ex) {
                return Response::json(['error' => [
                    'name' => 'gallery_not_found',
                    'message' => 'Gallery not found' 
                    ]
                ], 404);
        }
        catch(\Exception $e) {
            return Response::json(['error' => [
				'name' => 'guide_gallery_index_error',
				'message' => $e->getMessage()
				]
			],500);
        }
    }
}
