<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Offer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(User::all(),200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {

    }

    public function getPoints()
    {
        $user = auth()->user();
        return response()->json([
            'points' => $user->points,
        ], 200);
    }

    public function getUserProfile(){
        $user=auth()->user();
        return response()->json([
            'First Name'=>$user->first_name,
            'Last Name'=>$user->last_name,
            'Birth Date'=>$user->birth_date,
            'Gender'=>$user->gender,
            'Phone Number'=>$user->phone_number,
            'email'=>$user->email,
            'password'=>$user->password,
            'image'=>asset('storage/'.$user->image)
        ],200);
    }

    public function updateUserProfile(Request $request){
        $user = auth()->user();
        $validated = $request->validate([
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'birth_date' => 'sometimes|date',
            'gender' => 'sometimes|string',
            'phone_number' => 'sometimes|numeric|digits:10',
        ]);
        $user->fill($validated);
        $user->update();

        return response()->json(['Done'=>'profile edited successfully'],200);


    }

    public function updatePassword(Request $request){
        $request->validate([
            'old_password' => 'required',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        $user = auth()->user();
        if (! Hash::check($request->old_password,$user->password ) ){
            return response()->json([
                'message'=>'Old password does not match'],422);
        }
        $user->password=Hash::make($request->new_password);
        $user->update();
        return response()->json(['Done'=>'password changed successfully'],200);
    }






}
