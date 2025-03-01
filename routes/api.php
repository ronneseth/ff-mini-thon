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
    if (empty($customer_id)) {
        return response()->json(['message' => 'customer_id required'], 400);
    }

    if (empty($request->input('provider'))) {
        return response()->json(['message' => 'provider required'], 400);
    }
    $provider = (string)$request->input('provider');

	$providers = [];

	// Flagd provider
	$httpClient = new Client();
    $httpFactory = new HttpFactory();
    $flagd_provider = new FlagdProvider(['httpConfig' => new HttpConfig($httpClient,$httpFactory,$httpFactory,)]);
    $providers['flagd'] = $flagd_provider;

    // GoFeatureFlag provider
    $goff_provider = new GoFeatureFlagProvider(new Config('http://localhost:1031'));
    $providers['go-feature-flag'] = $goff_provider;

    if (!array_key_exists($provider, $providers)) {
        return response()->json(['message' => "{$provider} is not a valid provider"], 400);
    }

    // Get an instance of the OpenFeatureAPI
    // OpenFeatureAPI is a singleton that provides access to feature flag evaluation
    $api = OpenFeatureAPI::getInstance();
    $api->setProvider($providers[$provider]);
    $context = new MutableEvaluationContext('targetingKey', $attributes);
    $start = microtime(true);
    $value = $client->getBooleanValue("use-products-api", false, $context);
    $end = microtime(true);
    $total_time = $end - $start;
    if($value){
        return response()->json(['message' => 'Hello, World! PRODUCTS API - ' . $total_time]);
    }
    return response()->json(['message' => 'Hello, World! USING v1 - ' . $total_time]);
});

