# Behat3SymfonyExtension

[![Code Coverage](https://img.shields.io/scrutinizer/coverage/g/yoanm/Behat3SymfonyExtension.svg?label=Coverage)](https://scrutinizer-ci.com/g/yoanm/Behat3SymfonyExtension/?branch=master)

[![Scrutinizer Build Status](https://img.shields.io/scrutinizer/build/g/yoanm/Behat3SymfonyExtension.svg?label=Scrutinizer)](https://scrutinizer-ci.com/g/yoanm/Behat3SymfonyExtension/build-status/master) [![Scrutinizer Code Quality](https://img.shields.io/scrutinizer/g/yoanm/behat3SymfonyExtension.svg?label=Code%20quality)](https://scrutinizer-ci.com/g/yoanm/Behat3SymfonyExtension/?branch=master)

[![Travis Build Status](https://img.shields.io/travis/yoanm/Behat3SymfonyExtension.svg?label=travis)](https://travis-ci.org/yoanm/Behat3SymfonyExtension?label=Travis)

Behat3SymfonyExtension is a layer between Behat 3.0+ and Symfony3.0+, strongly inspired by [Symfony2Extension](https://github.com/Behat/Symfony2Extension).

It provide :
 * Context aware interfaces : 
    * [`KernelHandlerAwareInterface`](./src/Yoanm/Behat3SymfonyExtension/Context/KernelHandlerAwareInterface.php) : Will inject a [`KernelHandler`](./src/Yoanm/Behat3SymfonyExtension/Handler/KernelHandler.php) instance to play with your symfony application kernel (boot/shutdown/restart) in your behat contexts
    * [`LoggerAwareInterface`](./src/Yoanm/Behat3SymfonyExtension/Context/LoggerAwareInterface.php) : Will inject a monolog logger instance in your behat contexts
 * [`BehatContextSubscriberInterface`](./src/Yoanm/Behat3SymfonyExtension/Context/BehatContextSubscriberInterface.php) : Will allow your behat contexts to be aware of behat events (including [those](./src/Yoanm/Behat3SymfonyExtension/Event/Events.php) dispatched by this extension)
 * [`SfKernelEventLogger`](./src/Yoanm/Behat3SymfonyExtension/Logger/SfKernelEventLogger.php) : **Only in case where `kernel.debug` is set to true** (see kernel configuration below). 
 
 Produce a log entry each time that your symfony application kernel will : 
    - handle a request
    - catch an exception
    
 All data are loggued in the configured filed (see logger configuration below)
 
 
## Installation
```bash
> composer require --dev yoanm/behat3-symfony-extension
```

Behat3SymfonyExtension require [behat/behat](https://github.com/Behat/Behat), [monolog/monolog](https://github.com/Seldaek/monolog) and [symfony/framework-bundle](https://github.com/symfony/framework-bundle)

## Configuration
Add the following in your behat configuration file (usually `behat.yml`) : 
```yaml
default:
    extensions:
        Yoanm\Behat3SymfonyExtension: ~
```

To use the [`KernelDriver`](./src/Yoanm/Behat3SymfonyExtension/Driver/KernelDriver.php) for mink, install and configure [behat/mink-extension](https://github.com/Behat/MinkExtension)and [behat/mink-browserkit-driver](https://github.com/Behat/MinkBrowserKitDriver).
Then, add the following in your behat configuration file : 
```yaml
default:
    extensions:
        Behat\MinkExtension:
            sessions:
                my_session:
                    symfony2: ~
```
Be sure that symfony framework test mode is enabled in your application : 
```yaml
# app/config/config_test.yml
framework:
    test: ~
```

## Default configuration
```yaml
default:
    extensions:
        Yoanm\Behat3SymfonyExtension: 
            kernel:
                bootstrap: app/autoload.php
                path: app/AppKernel.php
                class: AppKernel
                env: test
                debug: true
                reboot: true # If true symfony kernel will be rebooted after each scenario/example
            logger:
                path: var/log/behat.log
                level: DEBUG
```
