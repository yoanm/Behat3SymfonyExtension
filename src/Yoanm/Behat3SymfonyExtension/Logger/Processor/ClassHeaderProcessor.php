<?php
namespace Yoanm\Behat3SymfonyExtension\Logger\Processor;

use Monolog\Logger;

/**
 * Will automatically add header with the calling class name
 * @codeCoverageIgnore
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
        $loggerFound = false;
        $classIndex = 2;
        foreach ($trace as $index => $traceData) {
            if (true === $loggerFound) {
                if (!isset($traceData['class']) || Logger::class !== $traceData['class']) {
                    $classIndex = $index;
                    break;
                }
            } else {
                $loggerFound = isset($traceData['class']) && Logger::class == $traceData['class'];
            }
        }

        return isset($trace[$classIndex]['class'])
            ? preg_replace('#(?:[^\\\]+\\\)#', '', $trace[$classIndex]['class'])
            : null;
    }
}
