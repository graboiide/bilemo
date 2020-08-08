<?php

namespace App\Controller;

use App\Entity\User;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class UserController
 * @package App\Controller
 * @Route("/api/users")
 */
class UserController extends BackController
{

   public function __construct(SerializerInterface $serializer)
   {
       parent::__construct($serializer);
       $this->params = [
           "self"=>["route"=>"api_users_items_get","id"=>true],
           "collection"=>["route"=>"api_users_collection_get","id"=>false],
           "add"=>["route"=>"api_users_collection_post","id"=>false],
           "delete"=>["route"=>"api_users_collection_delete","id"=>true]
       ];
   }

    /**
     * @Route(name="api_users_collection_get", methods={"GET"})
     * @param Request $request
     * @param UserRepository $userRepository
     * @return JsonResponse
     */
    public function collection(Request $request,UserRepository $userRepository):JsonResponse
    {
        $users = $this->getEntities($request,$userRepository);
        $all = [];
        foreach ($users as $user){
            $all[]= $this->links($user,$this->params,false,'users');
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
            $this->links($userRepository->findOneBy(['client'=>$this->getUser(),'id'=>$id]),$this->params),
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



}
