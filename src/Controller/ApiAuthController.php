<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/v1')]
class ApiAuthController extends AbstractController
{
    #[Route('/auth', name: 'api_auth')]
    public function auth(): Response
    {
        // get jwt token
    }
}
