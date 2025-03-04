# ff-mini-thon
Feature Flag Mini-thon

This is a test of using OpenFeature.org feature flag providers in Laravel.

# Usage

The API uses the following URI and parameters:

```
/api/ff/:feature_flag?provider=<provider>&customer_id=<customer_id>&product_hash=<hash>&flag_type=<type>&
```

- feature_flag: Try 'use_products_api'. This will take any value but will return 'false' if the FF is not valid
- provider: Can be either 'go-feature-flag' or 'flagd' (see below how to run providers)
- flag_type: Can be either boolean (RP lingo: FLAG), string (RP lingo: either ENUM or VALUE), or integer (RP lingo: LIMIT) or float (no RP lingo or example)
- product_hash: An 32 character hash representing an RP product. Can't put both c
- customer_id: An integer such as 99 or 1004

You can either specify product_hash or customer_id, but not both.

Example curl command
```
curl "http://127.0.0.1:8000/api/ff/max_listings?provider=flagd&flag_type=integer&product_hash=5c3fb11bdb8e1258e50e890dcd228faf"
```

Returns:
```
{"message":"integer max_listings 'productHash: 5c3fb11bdb8e1258e50e890dcd228faf' evaluated to 200000 - 0.001181988"}
```

## Local Development

```
git clone git@github.com:ronneseth/ff-mini-thon.git
cd ff-mini-thon
composer update
php artisan serve
```

The service will not work (throws 500 internal error) until we have a corresponding OpenFeature provider running. We support two - flagd and go-open-feature (goff).

### Flagd

Setup the flagd provider - you can read more on your own [here](https://flagd.dev/quick-start/).
```
mkdir flagd
cd flagd
wget https://raw.githubusercontent.com/ronneseth/ff-mini-thon/refs/heads/main/test-data/customer.flagd.json
docker run \
  --rm -it \
  --name flagd \
  -p 8013:8013 \
  -v $(pwd):/etc/flagd \
  ghcr.io/open-feature/flagd:latest start \
  --uri file:./etc/flagd/customer.flagd.json
```

With flagd running, test the flagd provider. You can revew customers.flagd.json to craft more examples

LIMIT example: http://127.0.0.1:8000/api/ff/max_listings?provider=flagd&flag_type=integer&product_hash=5c3fb11bdb8e1258e50e890dcd228faf  
```
{"message":"integer max_listings 'productHash: 5c3fb11bdb8e1258e50e890dcd228faf' evaluated to 200000 - 0.001181988"}
```
http://127.0.0.1:8000/api/ff/use_products_api?provider=flagd&customer_id=1004&flag_type=boolean
```
{"message":"boolean use_products_api 'customerId: 1004' evaluated to false - 0.000420951"}
```

Locally, flagd evaluates a feature flag in 3-7 ms. Memory usage:
```
root@thor-gen10:~# echo 0 $(awk '/Pss/ {print "+", $2}' /proc/`pidof flagd-build`/smaps) | bc
66060
```


### Go-Feature-Flag

Setup & run the go-feature-flag provider - you can read more on your own [here](https://gofeatureflag.org/docs/getting-started).

```
mkdir go-feature-flag
cd go-feature-flag
wget https://raw.githubusercontent.com/ronneseth/ff-mini-thon/refs/heads/main/test-data/customer-config.goff.yaml
wget https://raw.githubusercontent.com/ronneseth/ff-mini-thon/refs/heads/main/test-data/goff-proxy.yaml
docker run \
  -p 1031:1031 \
  -v $(pwd)/customer-config.goff.yaml:/goff/customer-config.goff.yaml \
  -v $(pwd)/goff-proxy.yaml:/goff/goff-proxy.yaml \
  gofeatureflag/go-feature-flag:latest
```
With go-feature-flag provider running you can test it. Check out customer-config.goff.yaml to craf more examples.

Off: http://127.0.0.1:8000/api/ff/use_products_api?provider=go-feature-flag&customer_id=99  
On:  http://127.0.0.1:8000/api/ff/use_products_api?provider=go-feature-flag&customer_id=1004

Locally, Goff evaluates a feature flag in 10-18 ms. Memory usage:
```
root@thor-gen10:~# echo 0 $(awk '/Pss/ {print "+", $2}' /proc/`pidof go-feature-flag`/smaps) | bc
115096

```


