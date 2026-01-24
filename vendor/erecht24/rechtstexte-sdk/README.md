# eRecht24 Rechtstexte-SDK
The eRecht24 Rechtstexte-SDK allows your service/server to interact with the eRecht24 Rechtstexte-API.
This package is under official supported by eRecht24.de.
We would recommend using this package in order to use the eRecht24 Rechtstexte-API services.

## Requirements
[PHP 7.1 or better](https://www.php.net/)

## Installation
Add the package using composer:

```shell
composer require erecht24/rechtstexte-sdk:"<2.0"
```

## Quickstart
### Create your API key
API keys may be generated using the [eRecht24 Projekt Manager](https://www.e-recht24.de/mitglieder/tools/projekt-manager/).
There is a key for development and testing purpose. Feel free to use it:

```e81cbf18a5239377aa4972773d34cc2b81ebc672879581bce29a0a4c414bf117```

### Getting your developer key (or plugin key)
Please note that all plugins contacting the eRecht24 Rechtstexte-API must send a verified developer key.
The developer key (or plugin key) is a unique key issued by eRecht24 to each developer to identify the different plugins communicating with the eRecht24 Rechtstexte-API.
Keys are issued after you signed our terms and conditions for the API. Please contact us: <a href="mailto:api@e-recht24.de">api@e-recht24.de</a>

### The legal text model
The [base model](./docs/legal_text.md#legal-text-model) for three different legal text types.

- [Imprint](./docs/legal_text.md#imprint)
- [Privacy policy](./docs/legal_text.md#privacy-policy)
- [Privacy policy for social media](./docs/legal_text.md#privacy-policy-social-media)

You may use the wrapper class to import legal text types:
```php
switch ($type) {
    case Helper::PUSH_TYPE_IMPRINT:
    case Helper::PUSH_TYPE_PRIVACY_POLICY:
    case Helper::PUSH_TYPE_PRIVACY_POLICY_SOCIAL_MEDIA:
        $legalTextHandler = new LegalTextHandler('YOUR_API_KEY', $type, 'YOUR-PLUGIN-KEY');
        /* @var LegalText $legalText */
        $legalTextDoc = $legalTextHandler->importDocument();
        $legalText = $legalTextDoc->getHtmlDE();
{ ... }
```
After getting a document object you can read the html text:
```php
if ($imprint = $apiHandler->getImprint()) {
    $html = $imprint->getHtmlDE();
}
```
or with dynmamic language support:
```php
if ($imprint = $apiHandler->getImprint()) {
    $html = $imprint->getHtml('en');
}
```

### The client model
Registered [clients](./docs/client.md) may receive push notifications.
```php
// new client data
$newClient = (new Client())
    ->setPushMethod('POST')
    ->setPushUri('https://test.de/push')
    ->setCms('WP')
    ->setCmsVersion('8.0')
    ->setPluginName('erecht24/rechtstexte-wp')
    ->setAuthorMail('test@test.de');
```
There is a limit of 3 clients per project.

### Usage :: code example
This simple example register a new client with your project (api-key) and get an actual html version of the imprint text. 

```php
// require composer autoloader, update/extend to your needs
// require_once '<path_to_project_root>/vendor/autoload.php';

use eRecht24\RechtstexteSDK\ApiHandler;
use eRecht24\RechtstexteSDK\Model\Client;
use eRecht24\RechtstexteSDK\Exceptions\Exception;

// initialize api handler
$apiHandler = new ApiHandler('YOUR-API-KEY', 'YOUR-PLUGIN-KEY');

// the new client data
$newClient = (new Client())
    ->setPushMethod('POST')
    ->setPushUri('https://test.de/push')
    ->setCms('WP')
    ->setCmsVersion('8.0')
    ->setPluginName('erecht24/rechtstexte-wp')
    ->setAuthorMail('test@test.de');

try {
    // create the new client
    $client = $apiHandler->createClient($newClient);

    if (!$apiHandler->isLastResponseSuccess()) {
        // do stuff in case of an error
    }

    if ($imprint = $apiHandler->getImprint()) {
        // example: get DE imprint
        $html = $imprint->getHtmlDE();
    }

} catch (Exception $e) {
    // as you need, log or rethrow here
}

// now go on with whatever service you want to execute
```
See full documentation of the [API handler](./docs/api_handler.md) for other service actions.

## Licence
Please check out our [Terms of use](LICENSE).

## Services
The eRecht24 Rechtstexte-API documentation can be found [here](https://api-docs.e-recht24.de/).










