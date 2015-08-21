# Laralytics

[![Build Status](https://travis-ci.org/bsharp/Laralytics.svg)](https://travis-ci.org/bsharp/Laralytics)
[![Latest Stable Version](https://poser.pugx.org/bsharp/laralytics/v/stable)](https://packagist.org/packages/bsharp/laralytics)
[![Total Downloads](https://poser.pugx.org/bsharp/laralytics/downloads)](https://packagist.org/packages/bsharp/laralytics)
[![License](https://poser.pugx.org/bsharp/laralytics/license)](https://packagist.org/packages/bsharp/laralytics)

## Installation

#### Composer

To install Laralytics as a Composer package to be used with Laravel 5.*, simply add this to your composer.json:

```
  "Bsharp/laralytics": "dev-master"
```

#### Add the service provider

Add this line to your `config/app.php` file in the service providers array:

```
Bsharp\Laralytics\LaralyticsServiceProvider::class,
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
  php artisan vendor:publish --tag=js          # For laralytics js file

```

### Configuration

Open the `config/laralytics.php` file, here you can specify:
- which driver to use with Laralytics
- the path to eloquent models if you use the Eloquent driver
- the method to retrieve an authentified user id's

### Laralytics js

Use the `vendor:publish` artisan command to add Laralytics js to your public directory

To start using Laralytics add the file to your view

```
  <script async src="{{ asset('js/laralytics.min.js') }}"></script>
```
And finally add this line in your view with this 

```
  <script type="text/javascript">
    laralytics.init();
  </script>
```

Or add it in a js extern file 

```
  laralytics.init();
```

If you want to config you can specify those parameters

```
  laralytics.init({
    API : your route where you want to collect your data,
    Version : If you want to know with A/B testing on which page you are,
    Limit : To set a limit when you want to send your data if set to 0 the data will be send when the user close his tab/browser or refresh the page
  });
```
