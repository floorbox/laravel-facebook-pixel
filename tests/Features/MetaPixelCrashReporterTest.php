<?php

use Symfony\Component\Process\Process;

it('does not enable SDK crash reporting when sending server events', function () {
    $process = new Process([PHP_BINARY, __DIR__.'/../Fixtures/CrashReporterProbe.php']);

    $process->run();

    $this->assertSame(0, $process->getExitCode(), $process->getErrorOutput());
});
