<?php
namespace Controller;

class Home extends \Uno\Controller
{
    public function index($param = NULL)
    {
        $this->msg = 'Uno Works.';
        $this->from = __METHOD__;
    }
}
