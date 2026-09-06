<?php

namespace Ifds\Interchange\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class Driver
{
    public function __construct(public readonly string $name) {}
}
