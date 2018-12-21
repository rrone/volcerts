<?php

namespace App\Controller;

use JonnyW\PhantomJs\Client;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class PhantomJSAction extends AbstractController
{
    private $projectDir;

    public function __construct($projectDir)
    {
        $this->projectDir = $projectDir;
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function index()
    {

        $url = 'https://national.ayso.org/Volunteers/ViewCertification?UserName=59282251';
        $testUrl = 'https://alanmbarr.github.io/HackMidWestTimeline/';
        $testUrl2 = 'http://jonnyw.me';

        $client = Client::getInstance();

        $client->getEngine()->setPath($this->projectDir . "/node_modules/phantomjs-prebuilt/bin/phantomjs");
        $client->getEngine()->debug(true);
        /**
         * @see JonnyW\PhantomJs\Http\Request
         **/
        $request = $client->getMessageFactory()->createRequest($url, 'GET');

        /**
         * @see JonnyW\PhantomJs\Http\Response
         **/
        $response = $client->getMessageFactory()->createResponse();

        // Send the request
        $client->send($request, $response);

        if($response->getStatus() === 200) {

            var_dump($response->getContent()); die();
            // Dump the requested page content
            echo $response->getContent();
        }

        return new Response();
    }

}