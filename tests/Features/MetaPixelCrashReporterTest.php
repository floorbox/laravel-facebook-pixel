<?php

use Symfony\Component\Process\Process;

it('does not enable SDK crash reporting when sending server events', function () {
    $process = new Process([PHP_BINARY, __DIR__.'/../Fixtures/CrashReporterProbe.php', 'disabled']);

    $process->run();

    $this->assertSame(0, $process->getExitCode(), $process->getErrorOutput());
});

it('enables SDK crash reporting through configuration', function () {
    $process = new Process([PHP_BINARY, __DIR__.'/../Fixtures/CrashReporterProbe.php', 'enabled']);

    $process->run();

    $this->assertSame(0, $process->getExitCode(), $process->getErrorOutput());
});
