<?php

namespace App\Http\Controllers;

use App\Models\FeatureAccess;
use App\Http\Requests\StoreFeatureAccessRequest;
use App\Http\Requests\UpdateFeatureAccessRequest;

class FeatureAccessController extends Controller
{
    private function featureAccessDisabledResponse()
    {
        abort(404);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return $this->featureAccessDisabledResponse();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return $this->featureAccessDisabledResponse();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreFeatureAccessRequest $request)
    {
        return $this->featureAccessDisabledResponse();
    }

    /**
     * Display the specified resource.
     */
    public function show(FeatureAccess $featureAccess)
    {
        return $this->featureAccessDisabledResponse();
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(FeatureAccess $featureAccess)
    {
        return $this->featureAccessDisabledResponse();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateFeatureAccessRequest $request, FeatureAccess $featureAccess)
    {
        return $this->featureAccessDisabledResponse();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(FeatureAccess $featureAccess)
    {
        return $this->featureAccessDisabledResponse();
    }
}
