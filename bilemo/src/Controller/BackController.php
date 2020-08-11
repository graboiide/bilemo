<?php


namespace App\Controller;


use App\Repository\UserRepository;
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
    public function __construct(SerializerInterface $serializer )
    {
        $this->serializer = $serializer;
    }

    function links($entity,$params,$json = true,$groups = null)
    {

        $userJson = $this->serializer->serialize($entity,'json',['groups'=>$groups]);
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

    /**
     * Recupere les données en fonction du numéro de page
     * @param PaginatorInterface $paginator
     * @param $data
     * @param Request $request
     * @return PaginationInterface
     */
    protected function dataForPage(PaginatorInterface $paginator,$data,Request $request)
    {
        $limit=count($data);
        //pagination
        if(!is_null($request->get('page')))
            $limit = 10;
        return $paginator->paginate($data,$request->query->getInt('page',1),$request->query->getInt('page_size',$limit));

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