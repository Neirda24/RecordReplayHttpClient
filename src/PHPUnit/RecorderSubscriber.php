<?php

namespace Symfony\HttpClientRecorderBundle\PHPUnit;

use PHPUnit\Event\Code\TestMethod;
use PHPUnit\Event\Test\PreparationStarted;
use PHPUnit\Event\Test\PreparationStartedSubscriber;
use Symfony\HttpClientRecorderBundle\Enum\RecorderMode;
use Symfony\HttpClientRecorderBundle\HttpClient\RecorderHttpClient;
use Symfony\HttpClientRecorderBundle\PHPUnit\Attribute\UseRecord;

final class RecorderSubscriber implements PreparationStartedSubscriber
{
    public function __construct(
        private string $defaultDirectory,
    ) {
        $this->defaultDirectory = \rtrim($this->defaultDirectory, '/').'/';
    }

    public function notify(PreparationStarted $event): void
    {
        RecorderHttpClient::setRecord('default.har');
        RecorderHttpClient::setMode(RecorderMode::PassThrough);

        $test = $event->test();

        if (!$test instanceof TestMethod) {
            return;
        }

        $className = $test->className();
        $methodName = $test->methodName();

        $attributeData = $this->loadUseRecordAttribute($className, $methodName);

        if (false === $attributeData) {
            return;
        }

        $currentTestDir = \dirname($test->file());

        $record = $attributeData[0] ?: $currentTestDir.'/'.$test->className().'/'.$test->methodName().'.har';
        $mode = $attributeData[1] ?: RecorderMode::NewEpisodes;

        if (\str_starts_with($record, '@')) {
            $record = \substr($record, 1);
            $record = "{$this->defaultDirectory}{$record}";
        } elseif (false === \str_starts_with($record, '/')) {
            $record = "{$currentTestDir}/{$record}";
        }

        RecorderHttpClient::setRecord($record); // TODO: When creating test for this method: make sure it is always absolute
        RecorderHttpClient::setMode($mode);
    }

    /**
     * @psalm-param class-string $className
     * @psalm-param class-string $methodName
     *
     * @psalm-return false|array{0: string, 1: RecorderMode}
     */
    private function loadUseRecordAttribute(string $className, string $methodName): false|array
    {
        $attributeFound = false;
        $mode = null;
        $record = null;

        if ($attributes = (new \ReflectionClass($className))->getAttributes(UseRecord::class)) {
            /** @var UseRecord $inst */
            $inst = $attributes[0]->newInstance();
            $record = $inst->record;
            $mode = $inst->mode;
            $attributeFound = true;
        }

        if ($attributes = (new \ReflectionMethod($className, $methodName))->getAttributes(UseRecord::class)) {
            /** @var UseRecord $inst */
            $inst = $attributes[0]->newInstance();
            $record = $inst->record; // TODO: handle if $record already ends with '/' and only allow "directories" not file ?
            $mode = $inst->mode ?: $mode;
            $attributeFound = true;
        }

        if (false === $attributeFound) {
            return false;
        }

        return [$record, $mode];
    }
}
