<?php 

namespace App\Contracts;

interface RepositoryInterface {

    /**
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder
     */
    public function newQuery();

    /**
     * Creates a Model object with the $data information.
     *
     * @param array $data
     *
     * @return Model
     */
    public function factory(array $data = []);

     /**
     * Retrieves a record by its id 
     * If $fail is true, a ModelNotFoundException is throwed.
     *
     * @param int  $id
     * @param array  $relations
     * @param bool $fail
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function findByID($id, $relations = null, $fail = true);

    /**
     * Returns all records.
     * Bring only informed $collumns
     * If $take is false then brings all records
     * If $paginate is true returns Paginator instance.
     *
     * @param string  $collumns
     * @param int  $take
     * @param bool $paginate
     *
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Pagination\AbstractPaginator
     */
    public function getAll($collumns = "*", $take = 15, $paginate = true);

    /**
     * @param array $data
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function create(array $data = []);

    /**
     * Performs the save method of the model
     * The goal is to enable the implementation of your business logic before the command.
     *
     * @param Model $model
     *
     * @return bool
     */
    public function save($model);

    /**
     * Run the delete command model.
     * The goal is to enable the implementation of your business logic before the command.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     *
     * @return bool
     */
    public function delete($model);
}