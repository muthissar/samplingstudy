<?php

namespace App\Mut;
use Symfony\Component\Yaml\Yaml;
class Config{
    public static function getConfig() : array{
        # NOTE : in case yaml_extension is not enabled.
        // return yaml_parse_file(__DIR__.'/../../config.yaml');
        return (array) Yaml::parseFile(__DIR__.'/../../config.yaml');
        // return [];
    }
}