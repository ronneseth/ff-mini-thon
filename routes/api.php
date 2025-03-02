<?php

declare(strict_types = 1);

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Inertia\Inertia;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;

use OpenFeature\OpenFeatureAPI;
use OpenFeature\implementation\flags\MutableEvaluationContext;
use OpenFeature\implementation\flags\Attributes;


use OpenFeature\Providers\Flagd\FlagdProvider;
use OpenFeature\Providers\Flagd\config\HttpConfig;

use OpenFeature\Providers\GoFeatureFlag\config\Config;
use OpenFeature\Providers\GoFeatureFlag\GoFeatureFlagProvider;


Route::get('/hello', function (Request $request) {

    $customer_id = filter_var($request->input('customer_id'), FILTER_VALIDATE_INT);

    // NOTE: the targetKey type MUST match the type of the target key in the feature flag
    //  -- e..g "1004", is not equal to 1004,
    //$customer_id = filter_var($request->input('customer_id'), FILTER_SANITIZE_SPECIAL_CHARS);
    if (empty($customer_id)) {
        return response()->json(['message' => 'customer_id required'], 400);
    }

    if (empty($request->input('provider'))) {
        return response()->json(['message' => 'provider required'], 400);
    }
    $provider = filter_var($request->input('provider'), FILTER_SANITIZE_SPECIAL_CHARS);
    if ($provider === false || empty($provider)) {
        return response()->json(['message' => 'Invalid provider'], 400);
    }

	$providers = [];

	// Flagd provider
	$httpClient = new Client();
    $httpFactory = new HttpFactory();
    $flagd_provider = new FlagdProvider(['httpConfig' => new HttpConfig($httpClient, $httpFactory, $httpFactory)]);
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
    // Let's evaluate the feature flag
    $attributes = new Attributes(['customerId' => $customer_id]);
    $context = new MutableEvaluationContext('targetingKey', $attributes);
    $start = hrtime(true);
    $client = $api->getClient(); 
    $value = $client->getBooleanValue("use-products-api", false, $context);
    $end = hrtime(true);
    $total_time = ($end - $start) / 1e9; // Convert nanoseconds to seconds
    if ($value) {
        return response()->json(['message' => 'Using PRODUCTS API - ' . $total_time]);
    }
    return response()->json(['message' => 'Using catalog v1 - ' . $total_time]);
});

