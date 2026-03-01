<?php

use Relaticle\Flowforge\Tests\StandaloneTestCase;
use Relaticle\Flowforge\Tests\TestCase;

pest()->extends(TestCase::class)->in('Feature', 'Unit');
pest()->extends(StandaloneTestCase::class)->in('Standalone');
