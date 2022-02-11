<?php

namespace Tests\Abstracts;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class WebTestCasePlus extends WebTestCase
{
    protected ContainerInterface $c;
    protected ?KernelBrowser $client;
    protected string $userName;
    protected string $pw;

    protected function setUp(): void
    {
        global $kernel;

        parent::setUp();

        $this->client = static::createClient(
            [
                'environment' => 'test',
                'debug' => true,
            ]
        );

        $this->client->catchExceptions(false);

        $kernel = $this->client->getKernel();

        $this->c = self::getContainer();

    }

 }
