<?php

class Route {

    // VARS

    public static $path = '';
    public static $q = [];

    // GENERAL

    public static function init() {
        Route::info();
        Route::route_common();
    }

    private static function info() {
        // vars
        $url = $_SERVER['REQUEST_URI'];
        // formatting
        if (substr($url, 0, 1) == '/') $url = substr($url, 1);
        $url = explode('?', $url);
        Route::$path = $url[0] ?? '';
        Route::$path = Route::$path ? flt_input(Route::$path) : 'plots';
        if (isset($url[1])) parse_str($url[1], $tmp);
        // escape data
        if (isset($tmp)) {
            foreach ($tmp as $key => $value) {
                $key = flt_input($key);
                $value = flt_input($value);
                Route::$q[$key] = $value;
            }
        }
    }

    // ROUTES

    private static function route_common() {
        if (Session::$access != 1) controller_login();
        else if (Route::$path == 'logout') Session::logout();
        else if (ModuleRegistry::handlePage(Route::$path, Route::$q)) return;
    }

    public static function route_call($path, $act, $data) {
        // routes
        if ($path == 'auth') $result = controller_auth($act, $data);
        // добавил хэндлер для user ajax запросов
        else if ($path == 'search') $result = controller_search($act, $data);
        else if (ModuleRegistry::hasApi($path)) $result = ModuleRegistry::handleApi($path, $act, $data);
        else $result = [];
        // output
        echo json_encode($result, true);
        exit();
    }

}