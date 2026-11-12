<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class WelcomeController extends Controller
{
    public function index()
    {
        return "Benvenuto in Jonathan gestionale alberghiero con Laravel!";
    }
}