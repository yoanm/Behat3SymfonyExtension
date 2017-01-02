# Behat3SymfonyExtension

[![Scrutinizer Build Status](https://img.shields.io/scrutinizer/build/g/yoanm/Behat3SymfonyExtension.svg?label=Scrutinizer)](https://scrutinizer-ci.com/g/yoanm/Behat3SymfonyExtension/?branch=master) [![Scrutinizer Code Quality](https://img.shields.io/scrutinizer/g/yoanm/Behat3SymfonyExtension.svg?label=Code%20quality)](https://scrutinizer-ci.com/g/yoanm/Behat3SymfonyExtension/?branch=master) [![Code Coverage](https://img.shields.io/scrutinizer/coverage/g/yoanm/Behat3SymfonyExtension.svg?label=Coverage)](https://scrutinizer-ci.com/g/yoanm/Behat3SymfonyExtension/?branch=master)

[![Travis Build Status](https://img.shields.io/travis/yoanm/Behat3SymfonyExtension/master.svg?label=travis)](https://travis-ci.org/yoanm/Behat3SymfonyExtension) [![PHP Versions](https://img.shields.io/badge/php-5.5%20%2F%205.6%20%2F%207.0-8892BF.svg)](https://php.net/) [![Symfony Versions](https://img.shields.io/badge/Symfony-2.7%20%2F%202.8%20%2F%203.0-312933.svg)](https://symfony.com/)

[![Latest Stable Version](https://img.shields.io/packagist/v/yoanm/behat3-symfony-extension.svg)](https://packagist.org/packages/yoanm/behat3-symfony-extension)

Behat3SymfonyExtension is a layer between Behat 3.0+ and Symfony2.7+|3+, strongly inspired by [Symfony2Extension](https://github.com/Behat/Symfony2Extension).

* [Why](#why)
* [Install](#install)
* [How to use](#how-to-use)
   * [Configuration](#configuration)
* [In the box](#in-the-box)
   * [`Client`](#client)
   * [`KernelAwareInterface`](#kernelawareinterface)
   * [`LoggerAwareInterface`](#loggerawareinterface)
   * [`SfKernelEventLogger`](#sfkerneleventlogger)
   * [Debug mode](#debug-mode)
* [Default configuration reference](#default-configuration-reference)
* [Tests](#tests)
* [Contributing](#contributing)

# Why
See [Why ? Or rather, why not Symfony2Extension ?](./doc/why-explanation.md#why--or-rather-why-not-symfony2extension-)

# Install
```bash
> composer require --dev yoanm/behat3-symfony-extension
```

# How to use
Behat3SymfonyExtension require [yoanm/behat-utils-extension](https://github.com/yoanm/BehatUtilsExtension), [behat/behat](https://github.com/Behat/Behat), [monolog/monolog](https://github.com/Seldaek/monolog), [symfony/browser-kit](https://github.com/symfony/browser-kit) and [symfony/framework-bundle](https://github.com/symfony/framework-bundle)

## Configuration
Add the following in your behat configuration file (usually `behat.yml`) : 
```yaml
default:
    extensions:
        Yoanm\BehatUtilsExtension: ~
        Yoanm\Behat3SymfonyExtension: ~
```

To use the `behat3Symfony` driver for mink (created thanks to [`Behat3SymfonyDriverFactory`](./src/Yoanm/Behat3SymfonyExtension/ServiceContainer/DriverFactory/Behat3SymfonyDriverFactory.php)), install and configure [behat/mink-extension](https://github.com/Behat/MinkExtension) and [behat/mink-browserkit-driver](https://github.com/Behat/MinkBrowserKitDriver).
Then, add the following in your behat configuration file : 
```yaml
default:
    extensions:
        Behat\MinkExtension:
            sessions:
                my_session:
                    behat3Symfony: ~
```

# In the box

## [`Client`](./src/Yoanm/Behat3SymfonyExtension/Client/Client.php)
It will be used by the mink driver if mink installed and configured to use the `behat3Symfony` driver

## [`KernelAwareInterface`](./src/Yoanm/Behat3SymfonyExtension/Context/KernelAwareInterface.php)
Will inject your symfony app kernel instance in your behat contexts

## [`LoggerAwareInterface`](./src/Yoanm/Behat3SymfonyExtension/Context/LoggerAwareInterface.php)
Will inject a monolog logger instance in your behat contexts
 
## [`SfKernelEventLogger`](./src/Yoanm/Behat3SymfonyExtension/Logger/SfKernelEventLogger.php) 

**Only in case where `kernel.debug` is set to true** (see default kernel configuration below). 
Produce a log entry each time that your symfony application kernel will : 
   - handle a request
   - catch an exception
    
All data are loggued in the configured file (see default logger configuration below)

## Debug mode
To enable extension debug mode, add the following in your behat configuration file :
```yaml
default:
    extensions:
        Yoanm\Behat3SymfonyExtension: 
            debug_mode: true
```
This mode allow two things : 
 * Kernel bridge class file is not deleted. If you have some errors related to the bridge, it will be easier for debug.
 * Some new log entry are added, regarding Kernel bridge and Client behavior
 
 In case you just want the log entry, just add the following in your behat configuration file : 
 ```yaml
 default:
     extensions:
         Yoanm\BehatUtilsExtension:
              logger:
                 level: DEBUG
 ```

# Configuration reference
```yaml
default:
    extensions:
        Yoanm\Behat3SymfonyExtension: 
            debug_mode: false
            kernel:
                bootstrap: app/autoload.php
                path: app/AppKernel.php
                class: AppKernel
                env: test
                debug: true
                reboot: true # If true symfony kernel will be rebooted BEFORE each scenario/example
```

# Tests
This repository follow a [custom test strategy](https://gist.github.com/yoanm/3944890d0adda5fc7e0c306a1870727d#file-tests-md)

# Contributing
See [contributing note](./CONTRIBUTING.md)
