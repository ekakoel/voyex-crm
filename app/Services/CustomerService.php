<?php

namespace App\Services;

use App\Repositories\CustomerRepository;

class CustomerService
{
    protected $repo;

    public function __construct(CustomerRepository $repo)
    {
        $this->repo = $repo;
    }

    public function getAll()
    {
        return $this->repo->all();
    }

    public function create($data)
    {
        $data['created_by'] = auth()->id();
        return $this->repo->store($data);
    }
}
