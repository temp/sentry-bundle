<?php

declare(strict_types=1);

namespace Temp\SentryBundle\Sentry;

use Jean85\PrettyVersions;
use Nyholm\Psr7\Factory\HttplugFactory;
use Nyholm\Psr7\Factory\Psr17Factory;
use Sentry\Client;
use Sentry\ClientBuilder;
use Sentry\HttpClient\HttpClientFactory;
use Sentry\Integration\RequestIntegration;
use Sentry\SentrySdk;
use Sentry\State\Hub;
use Sentry\State\HubInterface;
use Sentry\Transport\DefaultTransportFactory;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\HttplugClient;

final class SentryFactory
{
    public function create(
        ?string $dsn,
        string $environment,
        string $release,
        string $projectRoot,
        string $cacheDir,
        string $vendorDir,
        string $phpUname,
        string $phpSapiName,
        string $phpVersion,
        string $framework,
        string $symfonyVersion,
        string $symfonyEnvironment
    ): HubInterface {
        $clientBuilder = ClientBuilder::create([
            'dsn' => $dsn ?: null,
            'environment' => $environment, // I.e.: staging, testing, production, etc.
            'in_app_include' => [$projectRoot],
            'in_app_exclude' => [$cacheDir, $vendorDir],
            'prefixes' => [$projectRoot],
            'release' => $release,
            'default_integrations' => false,
            'send_attempts' => 1,
            'tags' => [
                'php_uname' => $phpUname,
                'php_sapi_name' => $phpSapiName,
                'php_version' => $phpVersion,
                'framework' => $framework,
                'symfony_version' => $symfonyVersion,
                'symfony_environment' => $symfonyEnvironment,
            ],
        ]);

        $client = HttpClient::create(['timeout' => 2]);
        $psr17Factory = new Psr17Factory();
        $httpClient = new HttplugClient($client, $psr17Factory, $psr17Factory);

        $httpPlugFactory   = new HttplugFactory();
        $httpClientFactory = new HttpClientFactory(
            $httpPlugFactory,
            $httpPlugFactory,
            $httpPlugFactory,
            $httpClient,
            Client::SDK_IDENTIFIER,
            PrettyVersions::getVersion('sentry/sentry')->getPrettyVersion()
        );

        $clientBuilder->setTransportFactory(new DefaultTransportFactory($httpPlugFactory, $httpClientFactory));

        // Enable Sentry RequestIntegration
        $options = $clientBuilder->getOptions();
        $options->setIntegrations([new RequestIntegration()]);

        $client = $clientBuilder->getClient();

        // A global HubInterface must be set otherwise some feature provided by the SDK does not work as they rely
        // on this global state
        return SentrySdk::setCurrentHub(new Hub($client));
    }
}
