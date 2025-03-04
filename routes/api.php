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


Route::get('/ff/{feature_flag}', function (Request $request, string $feature_flag) {

    // NOTE: the targetKey type MUST match the type of the target key in the feature flag
    //  -- e..g "1004", is not equal to 1004. Customer ID is always INT in the match in flagd
    $customer_id = filter_var($request->input('customer_id'), FILTER_VALIDATE_INT);
    $product_hash = filter_var($request->input('product_hash'), FILTER_SANITIZE_SPECIAL_CHARS);
    if (empty($product_hash) && empty($customer_id)) {
        return response()->json(['message' => 'EITHER product_hash OR customer_hash required'], 400);
    }
    if (!empty($product_hash) && !empty($customer_id)) {
        return response()->json(['message' => 'Only one of product_hash OR customer_hash required'], 400);
    }
    $context_variable = empty($customer_id) ? 'productHash' : 'customerId';
    $context_value = empty($customer_id) ? $product_hash : $customer_id;


    $flag_type = filter_var($request->input('flag_type'), FILTER_SANITIZE_SPECIAL_CHARS);
    if (empty($flag_type)) {
        return response()->json(['message' => 'flag_type required'], 400);
    }

    if ($flag_type !== 'boolean' && $flag_type !== 'string' && $flag_type !== 'integer' && $flag_type !== 'float') {
        return response()->json(['message' => 'Invalid flag_type'], 400);
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
    $attributes = new Attributes([$context_variable => $context_value]);
    $context = new MutableEvaluationContext('targetingKey', $attributes);
    $start = hrtime(true);
    $client = $api->getClient(); 
    if ($flag_type === 'boolean') {
        $value = $client->getBooleanValue($feature_flag, false, $context);
        // For "printability"
        if ($value === false) {
            $value = 'false';
        } elseif ($value === true) {
            $value = 'true';
        }
    } elseif ($flag_type === 'string') {
        $value = $client->getStringValue($feature_flag, '', $context);
        if ($value === '') {
            $value = '<empty string>';
        }
    } elseif ($flag_type === 'integer') {
        $value = $client->getIntegerValue($feature_flag, 0, $context);
    } elseif ($flag_type === 'float') {
        $value = $client->getFloatValue($feature_flag, 0, $context);
    }
    $end = hrtime(true);
    $total_time = ($end - $start) / 1e9; // Convert nanoseconds to seconds
    return response()->json(['message' => "{$flag_type} {$feature_flag} '{$context_variable}: {$context_value}' evaluated to {$value} - {$total_time}"]);
});

