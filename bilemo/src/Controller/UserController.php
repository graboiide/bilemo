<?php

namespace App\Controller;

use App\Entity\User;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;


use Knp\Component\Pager\Paginator;
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
     * Retourne une liste d'utilisateurs
     * @Route(name="api_users_collection_get", methods={"GET"})
     * @SWG\Response(
     *     response=200,
     *     description="Retourne la liste des utilisateurs",
     * )
     * @SWG\Parameter(
     *     name="page",
     *     in="query",
     *     type="integer",
     *     description="Page"
     * )
     * @SWG\Parameter(
     *     name="page_size",
     *     in="query",
     *     type="integer",
     *     description="nombre utilisateurs par page"
     * )
     * @SWG\Tag(name="users")
     * @Security(name="Bearer")
     * @param Request $request
     * @param UserRepository $userRepository
     * @param PaginatorInterface $paginator
     * @return JsonResponse
     * @throws InvalidArgumentException
     */
    public function collection(Request $request,UserRepository $userRepository,PaginatorInterface $paginator):Response
    {

        $users = $userRepository->findBy(['client'=>$this->getUser()]);
        $all = [];
        foreach ($users as $user){
            $all[]= $this->links($user,$this->params,false,'users');
        }
        $values = $all;

        $response = new Response( $this->serializer->serialize(
            $this->dataForPage($paginator,$values,$request),"json",['groups'=>'users']),Response::HTTP_OK,[
            'Content-Type' => 'application/json'
        ]);
        return $this->cacheHttp($response,$request);
    }


    /**
     * Retourne un utilisateur
     * @Route("/{id}",name="api_users_items_get", methods={"GET"})
     *  @SWG\Response(
     *     response=200,
     *     description="Retourne un utilisateur",
     *
     * )
     * @SWG\Parameter(
     *     name="id",
     *     in="path",
     *     type="integer",
     *     description="Identifiant de l'utilisateur"
     * )
     * @SWG\Tag(name="users")
     * @Security(name="Bearer")
     * @param $id
     * @param UserRepository $userRepository
     * @return JsonResponse
     */
    public function item($id,UserRepository $userRepository,Request $request):Response
    {
         new JsonResponse(
            $this->links($userRepository->findOneBy(['client'=>$this->getUser(),'id'=>$id]),$this->params,'true','users'),
            JsonResponse::HTTP_OK,[],
            true);
        $response = new Response(
            $this->links($userRepository->findOneBy(['client'=>$this->getUser(),'id'=>$id]),$this->params,'true','users'),

            Response::HTTP_OK,['Content-Type' => 'application/json']);
        return $this->cacheHttp($response,$request);
    }


    /**
     * Ajoute un utilisateur
     * @Route(name="api_users_collection_post", methods={"POST"})
     *  @SWG\Response(
     *     response=200,
     *     description="Ajoute un utilisateur",
     *
     * )
     * @SWG\Parameter(
     *     name="body",
     *          in="body",
     *          description="JSON Payload",
     *          required=true,
     *          type="json",
     *          format="application/json",
     *     @SWG\Schema(
     *              type="object",
     *              @SWG\Property(property="name", type="string", example="Lisa"),
     *              @SWG\Property(property="last_name", type="string", example="Bob"),
     *              @SWG\Property(property="avatar", type="string", example="http:www.avatar.com/avatar.png"),
     *              @SWG\Property(property="email", type="string", example="test@test.fr"),
     *          )
     * )
     * @SWG\Tag(name="users")
     * @Security(name="Bearer")
     * @param EntityManagerInterface $entityManager
     * @param Request $request
     * @param ValidatorInterface $validator
     * @return JsonResponse
     * @throws InvalidArgumentException
     */
    public function post( EntityManagerInterface $entityManager,Request $request,ValidatorInterface $validator):JsonResponse
    {

        /***@var User $user*/
        $user = $this->serializer->deserialize($request->getContent(),User::class,'json');
        $user->setClient($this->getUser());
        $errors = $validator->validate($user);
        //cas erreurs
        if(count($errors) > 0){
            $messages = [];
            foreach ($errors as $error){
                $messages[$error->getPropertyPath()][] = $error->getMessage();
            }
            return new JsonResponse(
                $messages,
                JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        $entityManager->persist($user);
        $entityManager->flush();
        $this->cache->delete('collection_users');
        return new JsonResponse(["code"=>JsonResponse::HTTP_CREATED,"message"=>"Utilisateur ajouter avec succes"],JsonResponse::HTTP_CREATED);
    }

    /**
     * Supprime un utilisateur
     * @Route("/{id}",name="api_users_collection_delete", methods={"DELETE"})
     * @SWG\Response(
     *     response=200,
     *     description="Supprime un utilisateur",
     *
     * )
     * @SWG\Parameter(
     *     name="id",
     *     in="query",
     *     type="integer",
     *     description="Identifiant de l'utilisateur à supprimer"
     * )
     * @SWG\Tag(name="users")
     * @Security(name="Bearer")
     * @param User $user
     * @param EntityManagerInterface $entityManager
     * @return JsonResponse
     * @throws InvalidArgumentException
     */
    public function delete(User $user,EntityManagerInterface $entityManager):JsonResponse
    {

        if($user->getClient() !== $this->getUser())
            return new JsonResponse([],JsonResponse::HTTP_FORBIDDEN);

        $entityManager->remove($user);
        $entityManager->flush();
        $this->cache->delete('collection_users');
        return new JsonResponse([],JsonResponse::HTTP_OK);
    }



}
