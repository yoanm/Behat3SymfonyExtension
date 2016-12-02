# Behat3SymfonyExtension

[![Code Coverage](https://img.shields.io/scrutinizer/coverage/g/yoanm/Behat3SymfonyExtension.svg?label=Coverage)](https://scrutinizer-ci.com/g/yoanm/Behat3SymfonyExtension/?branch=master)

[![Scrutinizer Build Status](https://img.shields.io/scrutinizer/build/g/yoanm/Behat3SymfonyExtension.svg?label=Scrutinizer)](https://scrutinizer-ci.com/g/yoanm/Behat3SymfonyExtension/build-status/master) [![Scrutinizer Code Quality](https://img.shields.io/scrutinizer/g/yoanm/behat3SymfonyExtension.svg?label=Code%20quality)](https://scrutinizer-ci.com/g/yoanm/Behat3SymfonyExtension/?branch=master)

[![Travis Build Status](https://img.shields.io/travis/yoanm/Behat3SymfonyExtension.svg?label=travis)](https://travis-ci.org/yoanm/Behat3SymfonyExtension?label=Travis)

Behat3SymfonyExtension is a layer between Behat 3.0+ and Symfony3.0+, strongly inspired by [Symfony2Extension](https://github.com/Behat/Symfony2Extension).

It provide : 
 * a [`KernelHandlerAwareInterface`](./src/Yoanm/Behat3SymfonyExtension/Context/KernelHandlerAwareInterface.php), it will inject a [`KernelHandler`](./src/Yoanm/Behat3SymfonyExtension/Handler/KernelHandler.php) instance to play with your symfony application kernel (boot/shutdown/restart)
 * a [`LoggerAwareInterface`](./src/Yoanm/Behat3SymfonyExtension/Context/LoggerAwareInterface.php), it will inject a monolog logger instance
 * a [`BehatContextSubscriberInterface`](./src/Yoanm/Behat3SymfonyExtension/Context/BehatContextSubscriberInterface.php), it will allow your context to be aware of behat events (including those dispatched by this library)
