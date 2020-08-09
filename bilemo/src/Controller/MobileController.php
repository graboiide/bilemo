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
     *
     * @SWG\Tag(name="mobiles")
     * @Security(name="Bearer")
     * @param MobileRepository $mobileRepository
     * @param Request $request
     * @param CacheInterface $cache
     * @param PaginatorInterface $paginator
     * @return JsonResponse
     * @throws InvalidArgumentException
     *
     *
     */
    public function collection(MobileRepository $mobileRepository,Request $request,CacheInterface $cache,PaginatorInterface $paginator):JsonResponse
    {

        //retourne le cache de la liste avec les links ou remet en cache au bout de 1 heures
        $values = $cache->get('collection-mobile',function (ItemInterface $item) use($mobileRepository){
            $item->expiresAfter(3600);
            $mobiles = $mobileRepository->findAll();
            $all = [];
            foreach ($mobiles as $mobile){
                $all[]= $this->links($mobile,$this->params,false,null);
            }
            return $all;
        });


        return new JsonResponse(
            $this->serializer->serialize(
                $this->dataForPage($paginator,$values,$request),"json",['groups'=>'users']),JsonResponse::HTTP_OK,[],true
        );
    }


    /**
     * Retourne un mobile
     * @Route("/{id}", name="api_mobile_item_get", methods={"GET"})
     * @SWG\Response(
     *     response=200,
     *     description="Retourne un mobile",
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
     * @return JsonResponse
     */
    public function item(Mobile $mobile=null):JsonResponse
    {
        if(is_null($mobile))
            return new JsonResponse("item not found",JsonResponse::HTTP_NOT_FOUND);
        return new JsonResponse(
            $this->links($mobile,$this->params)
            ,JsonResponse::HTTP_OK,[],true);
    }


}
