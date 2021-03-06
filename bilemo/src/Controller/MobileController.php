<?php

namespace App\Controller;

use App\Entity\Mobile;
use App\Repository\MobileRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

use Knp\Component\Pager\PaginatorInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use Swagger\Annotations as SWG;

use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Class MobileController
 * @package App\Controller
 * @Route("/api/mobiles")
 */
class MobileController extends BackController
{
    public function __construct(SerializerInterface $serializer)
    {
        parent::__construct($serializer);
        $this->params = [
            "self"=>["route"=>"api_mobile_item_get","id"=>true],
            "collection"=>["route"=>"api_mobile_collection_get","id"=>false],
            ];
    }

    /**
     * Retourne une liste de mobiles
     * @Route(name="api_mobile_collection_get", methods={"GET"})
     * @SWG\Response(
     *     response=200,
     *     description="Retourne la liste des mobiles",
     * )
     * @SWG\Parameter(
     *     name="page",
     *     in="query",
     *     type="integer",
     *     description="numero de la page"
     * )
     * @SWG\Parameter(
     *     name="page_size",
     *     in="query",
     *     type="integer",
     *     description="nombre de mobiles par pages"
     * )
     * @SWG\Parameter(
     *     name="order",
     *     in="query",
     *     type="string",
     *     description="Ordre croissant (ASC) ou décroissant (DESC)",
     *     default="ASC"
     * )
     * * @SWG\Parameter(
     *     name="filter",
     *     in="query",
     *     type="string",
     *     description="Filtrer sur un attribut (modelName,modelRef,price,cover,description)",
     *     default="id"
     * )
     *
     * @SWG\Tag(name="mobiles")
     * @Security(name="Bearer")
     * @param MobileRepository $mobileRepository
     * @param Request $request
     * @param CacheInterface $cache
     * @param PaginatorInterface $paginator
     * @return JsonResponse
     */
    public function collection(MobileRepository $mobileRepository,Request $request,CacheInterface $cache,PaginatorInterface $paginator):Response
    {

        $response = new Response( $this->serializer->serialize(
            $this->getData($request,$mobileRepository),"json"),200,[
            'Content-Type' => 'application/json'
        ]);
        return $this->cacheHttp($response,$request);

    }


    /**
     * Retourne un mobile
     * @Route("/{id}", name="api_mobile_item_get", methods={"GET"})
     * @SWG\Response(
     *     response=200,
     *     description="Retourne un mobile",
     *
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Mobile introuvable",
     *
     * )
     * @SWG\Parameter(
     *     name="id",
     *     in="path",
     *     type="integer",
     *     description="identifiant du mobile"
     * )
     * @SWG\Tag(name="mobiles")
     * @Security(name="Bearer")
     * @param Mobile|null $mobile
     * @param Request $request
     * @return Response
     */
    public function item(Mobile $mobile=null,Request $request):Response
    {
        if(is_null($mobile))
            return new JsonResponse(["code"=>JsonResponse::HTTP_NOT_FOUND],JsonResponse::HTTP_NOT_FOUND);

        $response = new Response( $this->links($mobile,$this->params),Response::HTTP_OK,[
            'Content-Type' => 'application/json'
        ]);
        return $this->cacheHttp($response,$request);
    }


}
