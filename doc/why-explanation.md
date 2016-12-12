# Why ? Or rather, why not Symfony2Extension ?
```
 *DISCLAIMER*

Goal here is not to blame Symfony2extension, but explaining why it does not fit my needs. 
Symfony2extension is a great extension, and has been a huge source of inspiration 
 to create this extensions.
```
Because mocking service container in behat tests with Symfony2Extension is not possible.
The main issue with Symfony2Extension is that it's not possible to dinamically inject mock data into container instance before calling the symfony application. 

That's not totally true in fact, it's possible but only in one specific case : when you launch behat with only one scenario that call your application only one time !

**Summary**
* [Not really a Symfony2Extension issue](#not-really-a-symfony2extension-issue-)
  * [Explanation by example](#explanation-by-example )
    * [:information_source: To know](#information_source-to-know)
    * [:ok_hand: Working use-case](#ok_hand-working-use-case)
    * [:heavy_exclamation_mark: Not handled use-case](#heavy_exclamation_mark-not-handled-use-case)
* [Why client reboot the kernel before a request ?](#why-client-reboot-the-kernel-before-a-request-)
* [How to inject data into service container with behat](#how-to-inject-data-into-service-container-with-behat)
  * [Implementation](#implementation)
    * [`features/bootstrap/MyThirdPartyApiContext.php`](#featuresbootstrapmythirdpartyapicontextphp)
    * [`features/bootstrap/App/Client/ThirdPartyClientMock.php`](#featuresbootstrapappclientthirdpartyclientmockphp)
    * [Side stuffs](#side-stuffs)
* [Kernel reboot events](#kernel-reboot-events)
  * [Use-case](#use-case)

## Not really a Symfony2Extension issue !
Issue is not really on Symfony2Extension but mainly on two points that result to a bigger :collision: points : 
 * **First :collision: point** 

   The driver used ([Framework bundle test client](https://github.com/symfony/framework-bundle/blob/master/Client.php)) automatically restart the kernel before doing a request **but only** in case a request has already been performed (see [there](https://github.com/symfony/framework-bundle/blob/2810e72dc11f74097b6d663312d1eef7ba41479b/Client.php#L118-L120)).

   Client do not really "reboot" the kernel, it shutdow it. And then when `Kernel::hande` is called, in case the kernel is not booted, it automatically boot itself (see [there](https://github.com/symfony/http-kernel/blob/c355df9479a065d243d17f708c8b65690ead4a9f/Kernel.php#L164-L166)).
   
   See ["Explanation by example"](#explanation-by-example ) and ["Why client reboot the kernel before a request ?"](#why-client-reboot-the-kernel-before-a-request-) below for deeper explanations.

 * **Second :collision: point**
   
   It's not possible to be notified when the kernel reboot (no event exists on symfony side).

   See [Kernel reboot events](#kernel-reboot-events) below for deeper explanations.

Because of this two :collision: points, it's not possible to know **when** injecting mock data.
 * For the first request to the kernel made over all the behat command execution (so first request of the first scenario executed), the container will be the one created at kernel startup.
 
   Container will be available from the beggining of the scenario, injected data that should be used during the next call to the kernel will work
   
 * For all other request to the kernel, as the kernel will be rebooted just before the request, the container will be a newly created one.
 
   So all data injected between the beggining of a scenario and the request to the kernel, have been injected into the container created just after the previous scenario (as Symfony2Extension reboot the kernel **after** each scenario)
   But it will be an empty container that will be used during the request to the kernel, that's the big :collision: point.
      
### Explanation by example 

#### :information_source: To know
 * Mocking service container for functional test could be done by using a container parameter for the service class and override it with a mock class in your `config_test.yml` like this :

```yaml
# app/config/services.yml
parameters:
    app.client.third_party.class: App\Client\ThirdPartyClient
services:
   app.client.third_party:
      class: %app.client.third_party.class%
```
```yaml
# app/config/config_test.yml
parameters:
#Declare your mock instead of the real class when test == env
    app.my_service.class: FunctionalTest\App\Client\ThirdPartyClientMock
```

 * Symfony2Extension kernel is booted when loaded from container (see [this](https://github.com/Behat/Symfony2Extension/blob/d78e54a70095c32123255c728c6680dc011e7aac/src/Behat/Symfony2Extension/ServiceContainer/Symfony2Extension.php#L179))
 * Symfony2Extension automatically reboot the kernel **after** each scenario (see [this](https://github.com/Behat/Symfony2Extension/blob/d78e54a70095c32123255c728c6680dc011e7aac/src/Behat/Symfony2Extension/Context/Initializer/KernelAwareInitializer.php#L45-L51) and [this](https://github.com/Behat/Symfony2Extension/blob/d78e54a70095c32123255c728c6680dc011e7aac/src/Behat/Symfony2Extension/Context/Initializer/KernelAwareInitializer.php#L68-L72))

#### :ok_hand: Working use-case
The following feature file will work if you launch behat with only that one (by specifing the file path, by filtering with tags or if you have only this feature file in your tests for instance) : 

```gherkin
Feature: Client/kernel behavior demo
 Scenario: First call to your symfony application
   # Next step will inject the returned data in the your mocked client (the one that will call your third-party endpoint for instance).
   # Your context that provide this step will implement the KernelAwareInterface of Symfony2Extension, 
   # in order to have access to your symfony app kernel and be able to inject data.
   # So, when this step will be executed, the container will be the one created when kernel has been booted
   Given my external api call will return:
   """
   { "ok": true }
   """
   # Next step will call your symfony app to the route that matche "/my_test_route". 
   # As no request have already been performed, Client will not reboot the kernel,
   # so container instance is the same than when the previous step have injected data
   # It goes perfectly
   When i call "/my_test_route"
   # No problem, now i can make assertions
   Then i should have ...
```

#### :heavy_exclamation_mark: Not handled use-case
But in case you launch more than one scenario (etheir with a feature with two or more scenarios or many features), for all steps that call your application except the first one performed, the kernel will be rebooted just before sending the request (in the below example it will be during the second call to the step `i call "/my_test_route"`). 

So this kind of feature will not work : 
```gherkin
Feature: Client/kernel behavior demo
 Scenario: First call to your symfony application
   # Next step will inject the returned data in the your mocked client (the one that will call your third-party endpoint for instance).
   # Your context that provide this step will implement the KernelAwareInterface of Symfony2Extension, 
   # in order to have access to your symfony app kernel and be able to inject data.
   # So, when this step will be executed, the container will be the one created when kernel has been booted
   Given my external api call will return:
   """
   { "ok": true }
   """
   # Next step will call your symfony app to the route that matche "/my_test_route". 
   # As no request have already been performed, Client will not reboot the kernel,
   # so container instance will be the same than when the previous step have injected data
   # It goes perfectly
   When i call "/my_test_route"
   Then i should have ...
 
 Scenario: An another call to your symfony application
   # Same than previously, next step will inject the returned data in the your mocked client.
   # As it's a new scenario, container have been rebooted just after the previous one, 
   # so a new container instance has been created. It will be the newly created container that will be used.
   Given my external api call will return:
   """
   { "ok": true }
   """
   # Like in previous scenario, next step will call your symfony app to the route that matche "/my_test_route". 
   # The big difference compared to previous scenario, is that, as a  request has been performed,
   # Client will reboot the container. So, data previously injected are deleted and a new container instance is created
   # So .... the call will fail as nothing has been specified for the return data of the
   When i call "/my_test_route" # <- Exception undefined key "ok" for instance
   Then i should have ...
```

## Why client reboot the kernel before a request ?
Rebooting the kernel is mandatory in order to reproduce the real behavior of symfony (one request/command = one kernel boot). 

Sadly, it create the issue described just above.

## How to inject data into service container with behat
The workaround rely on thwo things : 
 * Instead of resetting the kernel **after** each scenario, the [Behat3Symfony `Client`](../src/Yoanm/Behat3SymfonyExtension/Client/Client.php) will be reseted **before** each scenario (see [there](https://github.com/yoanm/Behat3SymfonyExtension/blob/545cd20417a0e49eb7b8c53c237d7a122aaee092/src/Yoanm/Behat3SymfonyExtension/Subscriber/RebootKernelSubscriber.php#L46)).
   And resetting the client will (re)boot the kernel (see [there](https://github.com/yoanm/Behat3SymfonyExtension/blob/545cd20417a0e49eb7b8c53c237d7a122aaee092/src/Yoanm/Behat3SymfonyExtension/Client/Client.php#L55))
 * An event is sent when a request will be sent (see [there](https://github.com/yoanm/Behat3SymfonyExtension/blob/545cd20417a0e49eb7b8c53c237d7a122aaee092/src/Yoanm/Behat3SymfonyExtension/Client/Client.php#L70-L73)).

Thanks to this new behavior, injecting data into service container could be easily managed by listening the [`Events::BEFORE_REQUEST`](https://github.com/yoanm/Behat3SymfonyExtension/blob/545cd20417a0e49eb7b8c53c237d7a122aaee092/src/Yoanm/Behat3SymfonyExtension/Event/Events.php#L8) event 
and injecting mock data when event is dispatched. 

Because, with the new behavior, it's sure that the container used by the kernel 
when the [`Events::BEFORE_REQUEST`](https://github.com/yoanm/Behat3SymfonyExtension/blob/545cd20417a0e49eb7b8c53c237d7a122aaee092/src/Yoanm/Behat3SymfonyExtension/Event/Events.php#L8) event is dispatched, will be the same than the one used during the request to the kernel (if it was required, the client has already rebooted the kernel).


### Implementation
*based on the [:heavy_exclamation_mark: Not handled use-case](#heavy_exclamation_mark-not-handled-use-case) example (feature, `config_dev.yml` and `services.yml` files)*

#### `features/bootstrap/MyThirdPartyApiContext.php`
```php
<?php
use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Symfony\Component\HttpKernel\KernelInterface;
use Yoanm\Behat3SymfonyExtension\Context\BehatContextSubscriberInterface;
use Yoanm\Behat3SymfonyExtension\Context\KernelAwareInterface;
use Yoanm\Behat3SymfonyExtension\Event\Events;

/**
 * Behavior :
 *  - This context listen on Events::BEFORE_REQUEST event thanks to BehatContextSubscriberInterface
 *  - It store fake data list thanks to myExternalApiCallWillReturn method until a request is sent to the kernel
 *  - When a request is sent, fetchMockDataList method will be called, and fake data list will be fetched to the third-party client mock
 *  - After each scenario, fake data list will be erased
 */
class MyThirdPartyApiContext implements Context, 
    KernelAwareInterface, 
    BehatContextSubscriberInterface
{
    /** @var KernelInterface */
    private $kernel;
    /** @var array */
    private $fakeDataList = [];

    /**
     * @Given /^my external api call will return:$/
     *
     * @param PyStringNode $data
     */
    public function myExternalApiCallWillReturn(PyStringNode $data)
    {
        $this->fakeDataList[] = json_encode($data->getRaw());
    }
    
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [Events::BEFORE_REQUEST => 'fetchMockDataList'];
    }
    
    /**
     * {@inheritdoc}
     */
    public function setKernel(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }
    
    public function resetFakeDataList()
    {
        $this->fakeDataList = [];
    }
    
    /**
     * {@inheritdoc}
     */
    public static function fetchMockDataList()
    {
        $this->kernel
          ->getContainer('app.client.third_party')
          ->setFakeDataList($this->fakeDataList);
        // list has been fetched => could be erased
        $this->resetFakeDataList();
    }
}
```
#### `features/bootstrap/App/Client/ThirdPartyClientMock.php`
```php
<?php
namespace FunctionalTest\App\Client;

use App\Client\ThirdPartyClient;

class ThirdPartyClientMock extends ThirdPartyClient
{
    /** @var array */
    private $fakeDataList = [];
  
    /**
     * @param array $fakeDataList
     */
    public function setFakeDataList(array $fakeDataList)
    {
        $this->fakeDataList = $fakeDataList;
    }

    /**
     * Override the real method (the one who really do the request to the third-party endpoint) to return configured data
     *
     * @return string the json response payload
     */
    protected function sendRequest(....)
    {
        return array_shift($this->fakeDataList);
    }
}
```

#### Side stuffs
In order to run this example, you will need :
 * to replace `ThirdPartyClient` by the real class of your client in the `services.yml` file example
 * to load `MyThirdPartyApiContext` by adding the following in your behat configuration file : 
```yaml
default:
  suites:
    default:
      contexts:
        - FunctionalTest\MyThirdPartyApiContext: ~
``` 
 * to allow PSR-4 autoloading of `FunctionalTest` namespace by adding the following in your `composer.json` :
```json
{
  ...
  "autoload-dev": {
    "psr-4": {
      "FunctionalTest\\": "features/bootstrap/"
    }
  },
  ...
}
```

## Kernel reboot events
As previously said, Symfony kernel does not provide events when it is booted or shutdown.

In order to have those events, a bridge is created between behat instance and your Symfony app kernel.

This bridge is created thanks to [KernelFactory](../src/Yoanm/Behat3SymfonyExtension/Factory/KernelFactory.php).

The factory take a [kernel template](https://github.com/yoanm/Behat3SymfonyExtension/blob/master/src/Yoanm/Behat3SymfonyExtension/Bridge/YoanmBehat3SymfonyKernelBridge.php) and [generate a random id](https://github.com/yoanm/Behat3SymfonyExtension/blob/8b8e0591927b919ab548841d9d22c7a7092ffe63/src/Yoanm/Behat3SymfonyExtension/Factory/KernelFactory.php#L53) to avoid name collisions. This kernel template [will extends your symfony app kernel](https://github.com/yoanm/Behat3SymfonyExtension/blob/8b8e0591927b919ab548841d9d22c7a7092ffe63/src/Yoanm/Behat3SymfonyExtension/Factory/KernelFactory.php#L71-L72) (see `__OriginalKernelClassNameToReplace__`) in order to be the most inconspicuous for you symfony app. 

In the reality, it's not an instance of your app kernel that is injected by [KernelAwareInitializer](https://github.com/yoanm/Behat3SymfonyExtension/blob/8b8e0591927b919ab548841d9d22c7a7092ffe63/src/Yoanm/Behat3SymfonyExtension/Context/Initializer/KernelAwareInitializer.php#L34), it's an instance of [YoanmBehat3SymfonyKernelBridgeXXXXXXXXXXXXX](https://github.com/yoanm/Behat3SymfonyExtension/blob/master/src/Yoanm/Behat3SymfonyExtension/Bridge/YoanmBehat3SymfonyKernelBridge.php) (the class file is [generated on the fly and deleted just after loading](https://github.com/yoanm/Behat3SymfonyExtension/blob/8b8e0591927b919ab548841d9d22c7a7092ffe63/src/Yoanm/Behat3SymfonyExtension/Factory/KernelFactory.php#L60-L81)) that **extends** your symfony app kernel.

Thanks to that, the bridge is transparent for you and your code but allow to implements events dispatch on [boot](https://github.com/yoanm/Behat3SymfonyExtension/blob/8b8e0591927b919ab548841d9d22c7a7092ffe63/src/Yoanm/Behat3SymfonyExtension/Bridge/YoanmBehat3SymfonyKernelBridge.php#L34-L39) and [shutdown](https://github.com/yoanm/Behat3SymfonyExtension/blob/8b8e0591927b919ab548841d9d22c7a7092ffe63/src/Yoanm/Behat3SymfonyExtension/Bridge/YoanmBehat3SymfonyKernelBridge.php#L47-L52) methods (see also [BehatKernelEventDispatcher](https://github.com/yoanm/Behat3SymfonyExtension/blob/master/src/Yoanm/Behat3SymfonyExtension/Dispatcher/BehatKernelEventDispatcher.php))

### Use-case
As an example, this extension use [Events::AFTER_KERNEL_BOOT](../src/Yoanm/Behat3SymfonyExtension/Event/Events.php) event to initialize [SfKernelEventLogger](https://github.com/yoanm/Behat3SymfonyExtension/blob/master/src/Yoanm/Behat3SymfonyExtension/Logger/SfKernelEventLogger.php) [when the container is created](https://github.com/yoanm/Behat3SymfonyExtension/blob/8b8e0591927b919ab548841d9d22c7a7092ffe63/src/Yoanm/Behat3SymfonyExtension/Subscriber/SfKernelLoggerSubscriber.php#L29-L45)
