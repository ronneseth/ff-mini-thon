# ff-mini-thon
Feature Flag Mini-thon

This is a test of using OpenFeature.org feature flag providers in Laravel.

Local Development

```
git clone git@github.com:ronneseth/ff-mini-thon.git
cd ff-mini-thon
composer update
php artisan serve
```

The service will not work (throws 500 internal error) until we have a corresponding OpenFeature provider running. We support two - flagd and go-open-feature (goff).

## Flagd

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

With flagd running, test the flagd provider. Customers in the range 1004 through 10000 has "products API" feature on - any others have it off:

Off: http://127.0.0.1:8000/api/ff/use_products_api?provider=flagd&customer_id=99  
On: http://127.0.0.1:8000/api/ff/use_products_api?provider=flagd&customer_id=1004

## Go-Feature-Flag

Setup & run the go-feature-flag provider - you can read more on your own here.

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
With go-feature-flag provider running you can test it. Customers in the range 1004 through 10000 has "products API" feature on - any others have it off:

Off: http://127.0.0.1:8000/api/ff/use_products_api?provider=go-feature-flag&customer_id=99  
On:  http://127.0.0.1:8000/api/ff/use_products_api?provider=go-feature-flag&customer_id=1004


