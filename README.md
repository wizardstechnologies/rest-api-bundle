Symfony bundle for wizards/php-rest-api

[![Build Status](https://travis-ci.org/wizardstechnologies/rest-api-bundle.svg?branch=master)](https://travis-ci.org/wizardstechnologies/rest-api-bundle)

Helps you create a REST API in an expressive and streamlined way.
It will help you conceive mature and discoverable APIs, thanks to the jsonapi specification.
Regardless of the output serialization, the request format will be the same.
Have a look at http://github.com/wizardstechnologies/php-rest-api for further documentation and explanations.
You can find an example project on https://github.com/BigZ/promoteapi

# Requirements
```
symfony >= 4.4
php >= 7.3
```

# Installation
```
composer require wizards/rest-bundle
```

# Configuration
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


If you use symfony flex, those services will be automatically registered.


To serialize a single resource, just return the object from a controller:
```
public function getArtistAction(string $id, EntityManagerInterface $entityManager)
{
    try {
        $artist = $entityManager->find(Artist::class, $id);
    } catch (\Exception $exception) {
        throw new NotFoundHttpException('Artist not found.');
    }

    return $artist;
}
```
Note that we don't use the param injector for the entity as we want to be able to dispatch an error ourselves
so it is properly formatted.


To Serialize a collection, use the collectionManager
```
public function getArtistsAction(CollectionManager $collectionManager, ServerRequestInterface $request)
{
    return $collectionManager->getPaginatedCollection(Artist::class, $request);
}
```


To deserialize input, use the json trait
```
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Wizards\RestBundle\Controller\JsonControllerTrait;

class ArtistController extends AbstractController
{
    use JsonControllerTrait;
	public function postArtistAction(Request $request, EntityManagerInterface $entityManager)
    {
        $artist = new Artist();
        $form = $this->createForm(ArtistType::class, $artist);
        $this->handleJsonForm($form, $request);

        if (!$form->isValid()) {
            $this->throwRestErrorFromForm($form);
        }

        $entityManager->persist($artist);
        $entityManager->flush();

        return $artist;
    }
}
```
