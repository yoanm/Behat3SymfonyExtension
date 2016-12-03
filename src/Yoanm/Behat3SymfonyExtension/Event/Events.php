<?php
namespace Yoanm\Behat3SymfonyExtension\Event;

class Events
{
    const BEFORE_KERNEL_SHUTDOWN = 'kernel_event.before.shutdown';
    const BEFORE_KERNEL_BOOT = 'kernel_event.before.boot';

    const AFTER_KERNEL_SHUTDOWN = 'kernel_event.after.shutdown';
    const AFTER_KERNEL_BOOT = 'kernel_event.after.boot';
}
