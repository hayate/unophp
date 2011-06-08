<?php

class HomeController extends Controller
{
    public function index()
    {
        $this->msg = 'Uno Works.';
        $this->from = __METHOD__;
    }
}
