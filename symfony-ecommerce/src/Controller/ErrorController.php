<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ErrorController extends AbstractController
{
    #[Route('/admin-access-denied', name: 'admin_access_denied')]
    public function adminAccessDenied(): Response
    {
        return $this->render('errors/admin_access_denied.html.twig', [], new Response('', 403));
    }
}
