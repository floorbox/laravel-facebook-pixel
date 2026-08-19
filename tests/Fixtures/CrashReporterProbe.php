<?php

use Combindma\FacebookPixel\MetaPixel;
use FacebookAds\CrashReporter;
use FacebookAds\Object\ServerSide\CustomData;
use FacebookAds\Object\ServerSide\UserData;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;

require dirname(__DIR__, 2).'/vendor/autoload.php';

$app = new Container;
$app->instance('config', new Repository([
    'meta-pixel' => [
        'advanced_matching_enabled' => false,
        'enabled' => true,
        'log_enabled' => false,
        'pixel_id' => 'pixel_id',
        'session_key' => 'session_key',
        'test_event_code' => null,
        'token' => 'token',
    ],
]));
$app->instance('url', new class
{
    public function current(): string
    {
        return 'https://example.com';
    }
});

Container::setInstance($app);
Facade::setFacadeApplication($app);

$eventRequest = Mockery::mock('overload:FacebookAds\Object\ServerSide\EventRequest');
$eventRequest->shouldReceive('setEvents')->andReturnSelf();
$eventRequest->shouldReceive('execute')->andReturnNull();

$crashLog = fopen('php://memory', 'w+');
CrashReporter::setLogger($crashLog);

(new MetaPixel)->send('TestEvent', 'EVENT_ID', new CustomData, new UserData);

rewind($crashLog);
$output = stream_get_contents($crashLog);

if (str_contains($output, 'FacebookAds\CrashReporter : Enabled')) {
    fwrite(STDERR, $output);
    exit(1);
}
