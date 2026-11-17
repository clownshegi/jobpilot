<?php

use App\Support\UrlGuard;

// IP literals keep these tests independent of DNS.

test('public http(s) addresses are allowed', function (string $url) {
    expect((new UrlGuard)->isAllowed($url))->toBeTrue();
})->with([
    'https://93.184.215.14/jobs/1',
    'http://8.8.8.8',
    'https://[2606:4700:4700::1111]/',
]);

test('internal addresses and other schemes are rejected', function (string $url) {
    expect((new UrlGuard)->isAllowed($url))->toBeFalse();
})->with([
    'loopback' => 'http://127.0.0.1:6379',
    'private network' => 'http://10.0.0.5/admin',
    'docker bridge' => 'http://172.17.0.1',
    'home router' => 'http://192.168.1.1',
    'cloud metadata' => 'http://169.254.169.254/latest/meta-data',
    'ipv6 loopback' => 'http://[::1]/',
    'unspecified' => 'http://0.0.0.0',
    'file scheme' => 'file:///etc/passwd',
    'gopher scheme' => 'gopher://93.184.215.14',
    'no host' => 'https:///jobs',
    'not a url' => 'just text',
]);

test('a host is rejected if any of its addresses is internal', function () {
    $guard = new class extends UrlGuard
    {
        protected function resolve(string $host): array
        {
            return ['93.184.215.14', '127.0.0.1'];
        }
    };

    expect($guard->isAllowed('https://rebind.example'))->toBeFalse();
});

test('a host that does not resolve is rejected', function () {
    $guard = new class extends UrlGuard
    {
        protected function resolve(string $host): array
        {
            return [];
        }
    };

    expect($guard->isAllowed('https://nowhere.invalid'))->toBeFalse();
});
