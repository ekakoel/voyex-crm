<?php

namespace App\Repositories;

use App\Models\Customer;

class CustomerRepository
{
    public function all()
    {
        return Customer::latest()->paginate(20);
    }

    public function store(array $data)
    {
        return Customer::create($data);
    }
}
