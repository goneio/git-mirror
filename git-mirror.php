#!/usr/bin/php
<?php
require_once(__DIR__ . "/bootstrap.php");

if(file_exists("mirrors.yml")) {
    \Mirror\Mirror::Factory()
        ->parse(\Symfony\Component\Yaml\Yaml::parseFile("mirrors.yml"))
        ->run();
}else{
    echo "Couldn't mirror! Cannot find mirrors.yml!";
}