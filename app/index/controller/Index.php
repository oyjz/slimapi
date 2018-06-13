<?php

namespace app\index\controller;

class Index
{
    public function index()
    {
        return ['message' => 'this is slim api default page.', 'guid'=>'654321'];
    }
}