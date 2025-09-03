<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use Faker\Generator;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class SecurityController extends AbstractController {


    public function __construct() {}

    #[Route(path: '/api/login', name: 'api_login', methods: ['POST'])]
    public function login() {
        $user = $this->getUser();
        return $this->json([
                    "emaillll" => $user->getEmail(),
                    'roles' => $user->getRoles()
        ]);
    }

    
}
