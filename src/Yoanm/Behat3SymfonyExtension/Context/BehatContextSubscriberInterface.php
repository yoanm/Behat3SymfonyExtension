<?php
namespace Yoanm\Behat3SymfonyExtension\Context;

use Behat\Behat\Context\Context;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Want to listen behat events (including this extension ones)
 * Just implement this interface your context will be pass to Behat dispatcher
 */
interface BehatContextSubscriberInterface extends Context, EventSubscriberInterface
{
}
