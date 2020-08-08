<?php

namespace App\Controller;

use App\Entity\User;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;


use Knp\Component\Pager\Paginator;
use Knp\Component\Pager\PaginatorInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Class UserController
 * @package App\Controller
 * @Route("/api/users")
 */
class UserController extends BackController
{
    private $cache;

   public function __construct(SerializerInterface $serializer,CacheInterface $cache)
   {
       parent::__construct($serializer);
       $this->cache = $cache;
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
     * @param PaginatorInterface $paginator
     * @return JsonResponse
     * @throws InvalidArgumentException
     */
    public function collection(Request $request,UserRepository $userRepository,PaginatorInterface $paginator):JsonResponse
    {

        $values = $this->cache->get('collection_users',
            function (ItemInterface $item)use($userRepository){
                $item->expiresAfter(3600);
                $users = $userRepository->findBy(['client'=>$this->getUser()]);
                $all = [];
                foreach ($users as $user){
                    $all[]= $this->links($user,$this->params,false,'users');
                }
                return $all;
        });

        return new JsonResponse(
            $this->serializer->serialize($this->dataForPage($paginator,$values,$request),"json",['groups'=>'users'])
            ,JsonResponse::HTTP_OK,[],true);
    }


    /**
     * @Route("/{id}",name="api_users_items_get", methods={"GET"})
     * @param $id
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
     * @param CacheInterface $cache
     * @return JsonResponse
     * @throws InvalidArgumentException
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
        $this->cache->delete('collection_users');
        return new JsonResponse([],JsonResponse::HTTP_CREATED);
    }

    /**
     * @Route("/{id}",name="api_users_collection_delete", methods={"DELETE"})
     * @param User $user
     * @param EntityManagerInterface $entityManager
     * @param UserRepository $userRepository
     * @param Request $request
     * @return JsonResponse
     * @throws InvalidArgumentException
     */
    public function delete(User $user,EntityManagerInterface $entityManager,UserRepository $userRepository,Request $request):JsonResponse
    {

        if($user->getClient() !== $this->getUser())
            return new JsonResponse([],JsonResponse::HTTP_FORBIDDEN);

        $entityManager->remove($user);
        $entityManager->flush();
        $this->cache->delete('collection_users');
        return new JsonResponse([],JsonResponse::HTTP_OK);
    }



}
