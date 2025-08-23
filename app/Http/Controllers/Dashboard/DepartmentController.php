<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Resources\DepartmentResource;
use App\Models\Department;
use Illuminate\Http\Request;
use Stichoza\GoogleTranslate\GoogleTranslate;

class DepartmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */


    public function index()
    {
        $departments =Department::with('doctors.employee')->get();
        return DepartmentResource::collection($departments);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated=$request->validate([
            'name' => 'required|unique:departments,name',
        ]);
        $tr= new GoogleTranslate();
        $tr->setSource('en');
        $tr->setTarget('ar');

        $en_name=$request->name;
        $ar_name=$tr->translate($en_name);
        $department = Department::create([
            'name'=>[
                'en' =>$en_name ,
                'ar' =>$ar_name ,
            ]
        ]);

        return response()->json([
            'message' => 'Department created successfully.',
            'department' => $department
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $department = Department::with('doctors.employee')->find($id);

        if (!$department) {
            return response()->json(['message' => 'Department not found'], 404);
        }

        return response()->json([
            'message' => 'Department and its doctors retrieved successfully',
         'department' =>  new DepartmentResource($department)
        ], 200);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:departments,name,' . $id,
        ]);

        $department =Department::with('doctors.employee')->find($id);

        if (!$department) {
            return response()->json(['message' => 'Department not found.'], 404);
        }
        $department->update($validated);
        return response()->json([
            'message' => 'Department updated successfully.',
            'department' => new DepartmentResource($department)
        ], 200);
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $department =Department::with('doctors.employee')->find($id);

        if (!$department) {
            return response()->json(['message' => 'Department not found'], 404);
        }
        $deletedepartment = new DepartmentResource($department);
        $department->delete();

        return response()->json(['message' => 'Department deleted successfully',
            'data'=>$deletedepartment],200);
    }
}
