<?php

namespace App\Controller;

use App\Entity\Mobile;
use App\Repository\MobileRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

use Symfony\Component\Serializer\SerializerInterface;

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
     * @Route(name="api_mobile_collection_get", methods={"GET"})
     * @return JsonResponse
     */
    public function collection(MobileRepository $mobileRepository,Request $request):JsonResponse
    {
        $mobiles = $this->getEntities($request,$mobileRepository);
        $all = [];
        foreach ($mobiles as $mobile){
            $all[]= $this->links($mobile,$this->params,false,null);
        }


        return new JsonResponse(
            $this->serializer->serialize($all,"json",['groups'=>'users'])
            ,JsonResponse::HTTP_OK,[],true);
    }


    /**
     * @Route("/{id}", name="api_mobile_item_get")
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
