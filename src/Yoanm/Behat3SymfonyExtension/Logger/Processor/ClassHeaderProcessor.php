<?php
namespace Yoanm\Behat3SymfonyExtension\Logger\Processor;

/**
 * Will automatically add header with the calling class name
 */
class ClassHeaderProcessor
{
    /**
     * @param  array $record
     * @return array
     */
    public function __invoke(array $record)
    {
        $record['message'] = sprintf(
            '[%s] %s',
            $this->getCallingClassName(),
            $record['message']
        );

        return $record;
    }

    /**
     * @return string|null
     */
    private function getCallingClassName()
    {
        $trace = debug_backtrace();

        return isset($trace[5]['class'])
            ? preg_replace('#(?:[^\\\]+\\\)#', '', $trace[5]['class'])
            : null;
    }
}
