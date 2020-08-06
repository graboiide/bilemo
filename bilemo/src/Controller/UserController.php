<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\MobileRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class MobileController
 * @package App\Controller
 * @Route("/api/users")
 */
class UserController extends AbstractController
{
    private $serializer;
    public function __construct(SerializerInterface $serializer )
    {
        $this->serializer = $serializer;
    }

    /**
     * @Route(name="api_users_collection_get", methods={"GET"})
     * @param EntityManagerInterface $entityManager
     * @return JsonResponse
     */
    public function collection(EntityManagerInterface $entityManager,UserRepository $userRepository):JsonResponse
    {
        $all = [];
        foreach ($userRepository->findBy(['client'=>$this->getUser()]) as $user){
            $all[]= $this->links($user,false);
        }
        return new JsonResponse(
           $this->serializer->serialize($all,"json",['groups'=>'users'])
            ,JsonResponse::HTTP_OK,[],true);
    }


    /**
     * @Route("/{id}",name="api_users_items_get", methods={"GET"})
     * @param $id
     * @param EntityManagerInterface $entityManager
     * @param UserRepository $userRepository
     * @return JsonResponse
     */
    public function item($id,UserRepository $userRepository):JsonResponse
    {
        return new JsonResponse(
            $this->links($userRepository->findOneBy(['client'=>$this->getUser(),'id'=>$id])),
            JsonResponse::HTTP_OK,[],
            true);
    }


    /**
     * @Route(name="api_users_collection_post", methods={"POST"})
     * @param EntityManagerInterface $entityManager
     * @param Request $request
     * @param ValidatorInterface $validator
     * @return JsonResponse
     */
    public function post( EntityManagerInterface $entityManager,Request $request,ValidatorInterface $validator):JsonResponse
    {

        /***@var User $user*/
        $user = $this->serializer->deserialize($request->getContent(),User::class,'json');
        $user->setClient($this->getUser());
       if($validator->validate($user)->count() > 0){
           return new JsonResponse([],JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
       }

        $entityManager->persist($user);
        $entityManager->flush();
        return new JsonResponse([],JsonResponse::HTTP_CREATED);
    }

    /**
     * @Route("/{id}",name="api_users_collection_delete", methods={"DELETE"})
     * @param EntityManagerInterface $entityManager
     * @param UserRepository $userRepository
     * @param Request $request
     * @return JsonResponse
     */
    public function delete(User $user,EntityManagerInterface $entityManager,UserRepository $userRepository,Request $request):JsonResponse
    {

        if($user->getClient() !== $this->getUser())
            return new JsonResponse([],JsonResponse::HTTP_FORBIDDEN);

        $entityManager->remove($user);
        $entityManager->flush();

        return new JsonResponse([],JsonResponse::HTTP_OK);
    }

    function links(User $user,$json = true)
    {

        $userJson = $this->serializer->serialize($user,'json',['groups'=>'users']);
        $userAssoc = json_decode($userJson,true);
        //dd($userAssoc);
        $userAssoc['_link'] = [
            'self'=>$this->generateUrl("api_users_items_get",['id'=>$user->getId()]),
            "collection"=>$this->generateUrl("api_users_collection_get"),
            'add'=>$this->generateUrl("api_users_collection_post"),
            'delete'=>$this->generateUrl("api_users_collection_delete",['id'=>$user->getId()])
        ];

        if($json)
            return json_encode($userAssoc,JSON_FORCE_OBJECT);
        return $userAssoc;
    }

}
