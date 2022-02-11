<?php


namespace App\Controller;


use App\Service\VolCertsTable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class VolCertsRESTAPI extends AbstractController
{
    /**
     * @var VolCertsTable $volCertsTable
     */
    private VolCertsTable $volCertsTable;

    /**
     * @var null | Request $reqest
     */
    private ?Request $request;

    /**
     * VolCertsTableController constructor
     * @param VolCertsTable $volCertsTable
     * @param RequestStack $requestStack
     */
    public function __construct(
        VolCertsTable $volCertsTable,
        RequestStack $requestStack
    )
    {
        $this->volCertsTable = $volCertsTable;

        $this->request = $requestStack->getCurrentRequest();
    }

    /**
     * @Route("/json/{names}", name="json")
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {

        $fileNames = explode(',', $this->request->get('names'));

        if(is_null($fileNames)) {
            return new JsonResponse(
                '',
                Response::HTTP_OK
            );
        }

        $file = '../var/uploads/' . $fileNames[0];

        if(!file_exists($file)) {
            return new JsonResponse(
                $fileNames[0] . ' not found.',
                Response::HTTP_OK
            );
        }

        $this->volCertsTable->retrieveVolCertData($file);
        $content = $this->volCertsTable->getDataOut(true);

        unlink($file);

        return new JsonResponse(
            $content,
            Response::HTTP_OK
        );

    }


}
