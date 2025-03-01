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

```
mkdir flagd
cd flagd
wget 
```


There are now 
http://127.0.0.1:8000/api/hello?customer_id=1003. You'll get an 500 error -- reason is we need to setup one of the providers.
