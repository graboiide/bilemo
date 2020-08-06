<?php

namespace App\DataFixtures;

use App\Entity\Client;
use App\Entity\Mobile;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Factory;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class AppFixtures extends Fixture
{
    private $passwordEncoder ;
    public function __construct(UserPasswordEncoderInterface $passwordEncoder)
    {
       $this->passwordEncoder = $passwordEncoder;
    }

    public function load(ObjectManager $manager)
    {
        $faker = Factory::create();
        // Mobile
        for($i=0;$i<30;$i++){
            $mobile = new Mobile();
            $mobile
                ->setModelName(implode(" ",$faker->words()))
                ->setModelRef($faker->password)
                ->setDescription(implode("",$faker->sentences))
                ->setCover($faker->imageUrl(640,480))
                ->setPrice($faker->numberBetween(700,1600));
            $manager->persist($mobile);
        }
        //Client
        $clients = [];
        for($i=0;$i<20;$i++){
            $client = new Client();
            $client
                ->setName($faker->name)
                ->setEmail($faker->email)
                ->setAdress($faker->address)
                ->setSurname($faker->lastName)
                ->setPassword($this->passwordEncoder->encodePassword($client,"123456"));
            if($i === 0){
                $client->setEmail("test@test.fr");
            }
            $manager->persist($client);
            $clients[] = $client;
        }

        //Users
        for($i=0;$i<50;$i++){
            $user = new User();
            $user->setEmail($faker->email)
                ->setName($faker->name)
                ->setLastName($faker->lastName)
                ->setAvatar($faker->imageUrl(300,300))
                ->setClient($clients[rand(0,11)]);
            $manager->persist($user);
        }
        $manager->flush();
    }
}
