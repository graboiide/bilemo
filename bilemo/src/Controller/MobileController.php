<?php

namespace App\Controller;

use App\Entity\Mobile;
use App\Repository\MobileRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

use Symfony\Component\Serializer\SerializerInterface;

/**
 * Class MobileController
 * @package App\Controller
 * @Route("/api/mobiles")
 */
class MobileController extends AbstractController
{
    /**
     * @Route(name="api_mobile_collection_get", methods={"GET"})
     * @param SerializerInterface $serializer
     * @param EntityManagerInterface $entityManager
     * @return JsonResponse
     */
    public function collection(SerializerInterface $serializer, EntityManagerInterface $entityManager,MobileRepository $mobileRepository):JsonResponse
    {
        return new JsonResponse(
            $serializer->serialize($mobileRepository->findAll(),"json")
            ,JsonResponse::HTTP_OK,[],true);
    }

    /**
     * @Route("/{id}", name="api_mobile_item_get")
     * @return JsonResponse
     */
    public function item(Mobile $mobile=null,SerializerInterface $serializer):JsonResponse
    {
        if(is_null($mobile))
            return new JsonResponse("item not found",JsonResponse::HTTP_NOT_FOUND);
        return new JsonResponse(
            $serializer->serialize($mobile,"json")
            ,JsonResponse::HTTP_OK,[],true);
    }
}
