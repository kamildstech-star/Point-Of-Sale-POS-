<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FrontendController extends Controller
{
    public function frontendIndex(){
        return view('frontend.index');
    }
    public function allProducts(){
        return view('frontend.all-products');
    }
    public function schoolIndex(){
        return view('frontend.schools');
    }
    public function contactIndex(){
        return view('frontend.contact');
    }
    public function createAccount(){
        return view('frontend.create-account');
    }
    public function account(){
        return view('frontend.account2');
    }
    public function address(){
        return view('frontend.address');
    }
}
