<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use OpenFeature\OpenFeatureAPI;
use OpenFeature\Providers\Flagd\FlagdProvider;
use OpenFeature\Providers\Flagd\config\HttpConfig;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;

Route::get('/hello', function () {
	$api = OpenFeatureAPI::getInstance();
	    $httpClient = new Client();
    $httpFactory = new HttpFactory();
    $api->setProvider(new FlagdProvider([
        'httpConfig' => new HttpConfig(
            $httpClient,
            $httpFactory,
            $httpFactory,
        )
    ]));
    $client = $api->getClient();
    if($client->getBooleanValue("welcome-message", false)){
        return response()->json(['message' => 'Hello, World! open feature']);
    }
    return response()->json(['message' => 'Hello, World!']);
});

