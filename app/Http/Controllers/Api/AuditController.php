<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\User;

class AuditController extends Controller
{
    public function stores($id){
        $user = User::find($id);

        return response()->json($user->stores);
    }
}
