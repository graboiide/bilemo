<?php


namespace App\Controller;


use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;

class BackController extends AbstractController
{
    protected $serializer;
    protected $params ;
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

    public function getEntities(Request $request,$repo)
    {
        $page = $request->get('p')?? 1;
        //empeche les nombres de négatif sans utiliser de if
        $page = max(1,$page);
        $limit = $request->get('limit')?? null;
        $order = $request->get('order')?[$request->get('order')=>"DESC"] : [];
        if(is_null($limit) && !is_null($request->get('p')))
            $limit = 10;
        /**@var UserRepository $repo*/

        return $repo->findBy([],$order,$limit,($page-1)*$limit);
    }
}