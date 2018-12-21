<?php

namespace App\Controller;

use Nesk\Puphpeteer\Puppeteer;
use Nesk\Rialto\Data\JsFunction;
use Nesk\Puphpeteer\Resources\ElementHandle;
use Sunra\PhpSimple\HtmlDomParser;

use Symfony\Component\HttpFoundation\Response;

class PuphpeteerAction
{
    private $projectDir;

    public function __construct($projectDir)
    {
        $this->projectDir = $projectDir;
    }

    public function index()
    {
        $url = 'https://national.ayso.org/Volunteers/ViewCertification?UserName=59282251';
        $testUrl = 'https://alanmbarr.github.io/HackMidWestTimeline/';

        $puppeteer = new Puppeteer();
        $browser = $puppeteer->launch();

        $page = $browser->newPage();
        $page->goto($url);

        $data = $page->evaluate(JsFunction::createWithBody('return document.documentElement.innerHTML'));
        $dom = HtmlDomParser::str_get_html( $data );
        $browser->close();

        foreach($dom->find('span') as $element) {
            echo $element->plaintext."\n";
        }

        $dom->clear();
        return new Response();
    }
}