<?php


namespace StormBin\Package\Controllers;


class Controller
{
    
    public static function model($model)
    {
        $modelName = "App\\Models\\$model";
        return new $modelName();
    }

  
    public static function jsonResponse($data)
    {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

   
    public static function renderView($view, $data = [])
    {
        
        extract($data);
        require "app/Views/$view.php";
    }
}
