<?php

namespace App\Repositories;

use App\Contracts\RepositoryInterface;

abstract class Repository implements RepositoryInterface {

    /**
     * Model class for repo.
     *
     * @var string
     */
    protected $modelClass;
    
    /**
     * @param \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder $query
     * @param int                               $take
     * @param bool                              $paginate
     *
     * @return \Illuminate\Database\Eloquent\Collection|\Illuminate\Pagination\AbstractPaginator
     */
    public function doQuery($query = null, $take = 15, $paginate = true)
    {
        if (is_null($query)) {
            $query = $this->newQuery();
        }
        if (true == $paginate) {
            return $query->paginate($take);
        }
        if ($take > 0 || false !== $take) {
            $query->take($take);
        }
        return $query->get();
    }

    public function newQuery() {
        return app()->make($this->modelClass)->newQuery();
    }

    /**
     * @param Model $model
     * @param array $data
     */
    protected function setModelData($model, array $data)
    {
        $model->fill($data);
    }

    public function factory(array $data = []) {
        $model = $this->newQuery()->getModel()->newInstance();
        $this->setModelData($model, $data);
        return $model;
    }

    public function findByID($id, $relations = null, $fail = true) {
        $query = $this->newQuery();
        if(isset($relations)) {
            $query = $query->with($relations);
        }

        if ($fail) {
            return $query->findOrFail($id);
        }
        return $query->find($id);
    }
    
    public function getAll($collumns = "*", $take = 15, $paginate = true) {
         $query = $this->newQuery()->select($collumns);
        return $this->doQuery($query, $take, $paginate);
    }
    
    public function exists($wheres) {
        return $this->newQuery()
            ->Where($wheres)
            ->count() > 0;
    }

    public function save($model) {
        return $model->save();
    }

    public function create(array $data = []) {
        
        $model = $this->factory($data);
        $this->save($model);
        return $model;
    }
    
    /**
     * Updated model data, using $data
     * The sequence performs the Model update.
     *
     * @param Model $model
     * @param array $data
     *
     * @return bool
     */
    public function update($id, array $data = []) {
        $model = $this->findByID($id);
        $this->setModelData($model, $data);
        $this->save($model);
        return $model;
    }

    public function delete($model) {
        return $model->delete();
    }
}