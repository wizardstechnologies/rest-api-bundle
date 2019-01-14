Symfony bundle for wizards/php-rest-api

[![Build Status](https://travis-ci.org/wizardstechnologies/rest-api-bundle.svg?branch=master)](https://travis-ci.org/wizardstechnologies/rest-api-bundle)

# Install
```
composer require wizards/rest-bundle
```

Register it in your AppKernel
```
new Wizards\RestBundle\WizardsRestBundle(),
```

# Configure
```
# config/bundles/wizards_rest.yaml
wizards_rest:
	data_source: orm|array
	reader: annotation|configuration|array
	format: jsonapi|array
	base_url: your_url
```

# Usage
Create a REST API the easy and configurable way !

This bundle ease the use of wizard's php rest api, and provide some extra goodies for symfony:
- a subscriber to automatically serialize your responses
- a subscriber to automatically serialize your exceptions
- a paramconverter to inject PSR-7 requests in your controllers
- a multi-part exception to easily serialize multiple errors (such as the one from forms)
- a controller trait that helps on serializing input data from json and jsonapi

# Further Documentation
Have a look at http://github.com/wizardstechnologies/php-rest-api

# TODO
- (MUST) Implement ODM
