<?php

/** Marcin Podraza */

/**
 * Use composer PSR-4 autoload
 *
 * First, run "composer init"
 * Then add "autoload" to composer.json
 * And then run "composer update"
 * After we need to require composer autoload
 * And use defined namespace (app) which is
 * root folder
 */

require_once __DIR__.'/../vendor/autoload.php';

use app\core\Application;                   // app (root folder)\core (subfolder with all required MVC modules)
use app\controllers\ErrorController;
use app\controllers\SiteController;

/* Start (bootstrap) Application */
$app = new Application(dirname(__DIR__));   // as we "use app\core\Application", we don't need to write new \app\core\Application()
$app->router
    ->get("/", [SiteController::class, 'home'], 'home', true)
    ->get("/contact", [SiteController::class, 'contact'], 'contact', true)
    ->post("/contact", [SiteController::class, 'contactPost'], 'contactPost', true)
    ->error(404, [ErrorController::class, 'notFound'], 'errorNotFound', true)
    ->error(500, [ErrorController::class, 'internal'], 'errorInternal', true);
$app->run();
