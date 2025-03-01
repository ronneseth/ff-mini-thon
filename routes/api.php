<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Inertia\Inertia;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;

use OpenFeature\OpenFeatureAPI;
use OpenFeature\Interfaces\Flags;
use OpenFeature\implementation\flags\MutableEvaluationContext;
use OpenFeature\implementation\flags\Attributes;


use OpenFeature\Providers\Flagd\FlagdProvider;
use OpenFeature\Providers\Flagd\config\HttpConfig;

use OpenFeature\Providers\GoFeatureFlag\config\Config;
use OpenFeature\Providers\GoFeatureFlag\GoFeatureFlagProvider;


Route::get('/hello', function (Request $request) {

	$customer_id = (int)$request->input('customer_id');
	$api = OpenFeatureAPI::getInstance();

    $provider =null;
    if (false)    
    {
	    $httpClient = new Client();
        $httpFactory = new HttpFactory();
        $provider = new FlagdProvider(['httpConfig' => new HttpConfig($httpClient,$httpFactory,$httpFactory,)]);
    }
    else
    {
        $provider = new GoFeatureFlagProvider(new Config('http://localhost:1031'));
    }
    $api->setProvider($provider);
    $client = $api->getClient();
    $attributes = new Attributes(['customerId' => $customer_id]);
    $start = microtime(true);
    $context = new MutableEvaluationContext('targetingKey', $attributes);
    $end = microtime(true);
    $value = $client->getBooleanValue("use-products-api", false, $context);
    $total_time = $end - $start;
    if($value){
        return response()->json(['message' => 'Hello, World! PRODUCTS API - ' . $total_time]);
    }
    return response()->json(['message' => 'Hello, World! USING v1 - ' . $total_time]);
});

