# Laralytics

## Installation

#### Composer

To install Laralytics as a Composer package to be used with Laravel 5.*, simply add this to your composer.json:

```
  "Bsharp/laralytics": "dev-master"
```

### Publish

To add all laralytics resources to your app you need to publish them using the `vendor:publish` artisan command

```
  php artisan vendor:publish
```

You can specify which resource to publish one by one using:

```
  php artisan vendor:publish --tag=config      # Laralytics configuration
  php artisan vendor:publish --tag=migrations  # Laralytics migrations
  php artisan vendor:publish --tag=middleware  # (optional) Generic middleware to log your app action
  php artisan vendor:publish --tag=eloquent    # For eloquent driver only

```

### Configuration

Open the `config/laralytics.php` file, here you can specify:
- which driver to use with Laralytics
- the path to eloquent models if you use the Eloquent driver
- the method to retrieve an authentified user id's
