<?php

namespace Symfony\HttpClientRecorderBundle\PHPUnit\Attribute;

use Symfony\HttpClientRecorderBundle\Enum\RecorderMode;

/**
 * @example UseRecord('my_record.har', RecorderMode::Record)
 * @example UseRecord('./my_record.har', RecorderMode::Record)
 * @example UseRecord('../my_record.har', RecorderMode::Record)
 * @example UseRecord('/my_record.har', RecorderMode::Record)
 * @example UseRecord('@my_record.har', RecorderMode::Record)
 */
#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::TARGET_CLASS)]
final readonly class UseRecord
{
    public function __construct(
        public ?string $record = null,
        public ?RecorderMode $mode = null,
    ) {
    }
}
