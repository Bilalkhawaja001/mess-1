<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class HubController extends Controller
{
    public function operations()
    {
        return view('admin.hubs.operations');
    }

    public function reports()
    {
        return view('admin.hubs.reports');
    }

    public function inventory()
    {
        return view('admin.hubs.inventory');
    }

    public function meals()
    {
        return view('admin.hubs.meals');
    }
}
