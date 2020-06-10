Symfony bundle for wizards/php-rest-api

[![Build Status](https://travis-ci.org/wizardstechnologies/rest-api-bundle.svg?branch=master)](https://travis-ci.org/wizardstechnologies/rest-api-bundle)

Helps you create a REST API in an expressive and streamlined way.
It will help you conceive mature and discoverable APIs, thanks to the jsonapi specification.
Regardless of the output serialization, the request format will be the same.
Have a look at http://github.com/wizardstechnologies/php-rest-api for further documentation and explanations.

# Requirements
```
symfony >= 4.3
```

# Install
```
composer require wizards/rest-bundle
```

# Configure
Create a configuration file with the following values:

```
# config/bundles/wizards_rest.yaml
wizards_rest:
	data_source: orm|array # Choose between ORM and Array for your data source. More will be added soon
	reader: annotation|array
	format: jsonapi|array
	base_url: your_url
```

# Usage
Create a REST API the easy and configurable way !

This bundle ease the use of wizard's php rest api, and provide some extra goodies for symfony:
- [a subscriber](https://github.com/wizardstechnologies/rest-api-bundle/blob/master/Subscriber/SerializationSubscriber.php) to automatically serialize your responses
- [a subscriber](https://github.com/wizardstechnologies/rest-api-bundle/blob/master/Subscriber/ExceptionSubscriber.php) to automatically serialize your exceptions
- [a param converter](https://github.com/wizardstechnologies/rest-api-bundle/blob/master/Subscriber/ExceptionSubscriber.php) to inject PSR-7 requests in your controllers
- [a multi-part exception](https://github.com/wizardstechnologies/rest-api-bundle/blob/master/ParamConverter/Psr7ParamConverter.php) to easily serialize multiple errors (such as the one from forms)
- [a controller trait](https://github.com/wizardstechnologies/rest-api-bundle/blob/master/Controller/JsonControllerTrait.php) that helps on serializing input data from json and jsonapi

# Documentation

- Configure your data source
- Expose your endpoints

