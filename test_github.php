<?php

require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$token = $_ENV['GITHUB_TOKEN'];

$client = new \Github\Client();
$client->authenticate($token, null, \Github\AuthMethod::ACCESS_TOKEN);

try {
    $user = $client->api('user')->show('chetanwpexperts');
    echo "Connected! GitHub user: " . $user['login'] . "\n";
    echo "Name: " . ($user['name'] ?? 'N/A') . "\n";
    echo "Public repos: " . $user['public_repos'] . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}