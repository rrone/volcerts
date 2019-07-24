<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class ProgressBarController
 * @package App\Controller
 */
class ProgressBarController extends AbstractController
{
    /**
     * ProgressBarController constructor.
     */
    public function __construct()
    {

    }

    /**
     * @Route("/pb", name="app_pb")
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Exception
     */
    public function __invoke()
    {

        $response = $this->render('pb.html.twig');
        $response->headers->set('Content-Type', 'text/html');
        $response->headers->set('Cache-Control', 'no-cache');

        return $response;
    }

    /**
     * @Route("/slo", name="app_slo")
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function renderContent()
    {
        $k = 10;

        //LONG RUNNING TASK
        for ($i = 1; $i <= $k; $i++) {
            $this->send_message($i, 'on iteration '.$i." of $k", $i * 100 / $k);

            sleep(1);
        }

        $this->send_message('CLOSE', 'Process complete');

        return $this->redirect('/pb');

    }

    /**
     * @param $id
     * @param $message
     * @param $progress
     */
    private function send_message($id, $message, $progress = 100)
    {
        $d = array('message' => $message, 'progress' => $progress);

        echo "id: $id".PHP_EOL;
        echo "data: ".json_encode($d).PHP_EOL;
        echo PHP_EOL;

        ob_flush();
        flush();
    }

}