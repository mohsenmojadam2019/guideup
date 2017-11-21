<?php

namespace App\Repositories;

use App\Repositories\Repository;
use App\Models\Feedback;

class FeedbackRepository extends Repository {
    
    protected $modelClass = Feedback::class;
        
    public function search(array $array = [], $collumns = ["*"]) {
        $feedbacks = $this->newQuery();
        if(isset($array['email'])) {
            $feedbacks = $feedbacks->where('email', '=', $array['email']);
        }
        if(isset($array['term'])) {
            $feedbacks = $feedbacks
            ->where('description','like','%'.$array['term'].'%')
            ->orWhere('name','like','%'.$array['term'].'%');
        }
        
        if(isset($array['responded'])) {
            $responded = $array['responded'];
        if($responded == 'true' || $responded == 'yes' || $responded == 'y') {
                $feedbacks = $feedbacks->whereNotNull('response');
            } else if($responded == 'false' || $responded == 'no' || $responded == 'n') {
                $feedbacks = $feedbacks->whereNull('response');
            }
        }

        $take = 15;
        if(isset($array['total'])) {
            $take = $array['total'];
        }
        
        if(isset($array['count'])) {
            return ["total" => $feedbacks->count()];
        }

        $feedbacks = $feedbacks->orderBy('created_at', 'desc');

        $feedbacks = $feedbacks->select($collumns);

        return $this->doQuery($feedbacks, $take);
    }
}