<?php

use Combindma\FacebookPixel\MetaPixel;
use FacebookAds\CrashReporter;
use FacebookAds\Object\ServerSide\CustomData;
use FacebookAds\Object\ServerSide\UserData;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;

require dirname(__DIR__, 2).'/vendor/autoload.php';

$expectedCrashReportingState = $argv[1] ?? 'disabled';

if ($expectedCrashReportingState === 'enabled') {
    putenv('META_PIXEL_CRASH_REPORTING_ENABLED=true');
} else {
    putenv('META_PIXEL_CRASH_REPORTING_ENABLED');
    unset($_ENV['META_PIXEL_CRASH_REPORTING_ENABLED'], $_SERVER['META_PIXEL_CRASH_REPORTING_ENABLED']);
}

$app = new Container;
$config = new Repository(['app' => ['name' => 'Test App']]);
$app->instance('config', $config);
$app->instance('url', new class
{
    public function current(): string
    {
        return 'https://example.com';
    }
});

Container::setInstance($app);
Facade::setFacadeApplication($app);

$config->set('meta-pixel', require dirname(__DIR__, 2).'/config/meta-pixel.php');
$config->set('meta-pixel.enabled', true);
$config->set('meta-pixel.pixel_id', 'pixel_id');
$config->set('meta-pixel.token', 'token');

$eventRequest = Mockery::mock('overload:FacebookAds\Object\ServerSide\EventRequest');
$eventRequest->shouldReceive('setEvents')->andReturnSelf();
$eventRequest->shouldReceive('execute')->andReturnNull();

$crashLog = fopen('php://memory', 'w+');
CrashReporter::setLogger($crashLog);

(new MetaPixel)->send('TestEvent', 'EVENT_ID', new CustomData, new UserData);

rewind($crashLog);
$output = stream_get_contents($crashLog);
$crashReportingWasEnabled = str_contains($output, 'FacebookAds\CrashReporter : Enabled');
$crashReportingShouldBeEnabled = $expectedCrashReportingState === 'enabled';

if ($crashReportingWasEnabled !== $crashReportingShouldBeEnabled) {
    fwrite(STDERR, sprintf(
        "Expected crash reporting to be %s, but it was %s.\n%s",
        $expectedCrashReportingState,
        $crashReportingWasEnabled ? 'enabled' : 'disabled',
        $output,
    ));
    exit(1);
}
