<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;

class GetThings extends Controller
{
    public function getDepartments(){
        return response()->json(Department::all());
    }

    //Do not forget to CRUD all your Controllers
}
