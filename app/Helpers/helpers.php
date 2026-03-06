<?php


if(!function_exists('includeRouteFiles')) {
    function includeRouteFiles($folder): void
    {
        // function glob() scan folder and return all file in array
        foreach(glob($folder.'/*.php') as $routeFile) {
            require $routeFile;
        }
    }
}