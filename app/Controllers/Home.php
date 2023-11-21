<?php

namespace App\Controllers;

class Home extends BaseController
{
    public function index(): string
    {
        return view('welcome_message');
    }

    public function database(): string
    {
        return view('database');
    }

    public function ttx(): string
    {
        return view('ttx');
    }
}
