<?php


namespace App\Controller;


use App\Repository\UserRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;

class BackController extends AbstractController
{
    protected $serializer;
    protected $params ;
    protected $request;
    protected $group ;
    public function __construct(SerializerInterface $serializer )
    {
        $this->serializer = $serializer;
    }

    function links($entity,$params,$json = true)
    {
        if(is_null($entity))
            return null;
        $userJson = $this->serializer->serialize($entity,'json',['groups'=>$this->group]);
        $userAssoc = json_decode($userJson,true);
        $links = [];
        foreach ($params as $key => $param){
            if($param["id"])
               $links[$key]=['href'=>$this->generateUrl($param["route"],["id"=>$entity->getId()])];
            else
                $links[$key]=['href'=>$this->generateUrl($param["route"])];
        }
        $userAssoc['_link'] = $links;
        if($json)
            return json_encode($userAssoc,JSON_FORCE_OBJECT);
        return $userAssoc;
    }

    protected function getData(Request $request,ServiceEntityRepository $repo,$criteria = [])
    {
        $limit = $request->query->getInt('page_size',$repo->count([]));
        $page = max(0,$request->query->getInt('page',1));
        $order = $request->query->getAlpha('order','ASC');
        $filter = $request->query->getAlpha('filter','id');
        $entities = $repo->findBy($criteria,[$filter=>$order],$limit,($page-1)*$limit);

        $all = [];
        foreach ($entities as $entity){
            $all[]= $this->links($entity,$this->params,false);
        }
        return $all;

    }
    /**
     * Active le httpcache de symfony avec validation depuis le Etag
     * @param Response $response
     * @param Request $request
     *
     * @return Response
     */
    protected function cacheHttp(Response $response,Request $request)
    {
        $response->setPublic();

        $response->headers->addCacheControlDirective('must-revalidate', true);

        $response->setEtag(md5($response->getContent()));
        //compare l'etag
        $response->isNotModified($request);
        //Si pas modifié 304 sinon 200
        return $response;
    }


}