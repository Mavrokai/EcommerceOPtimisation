<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pages légales et statiques référencées dans config/routes.yaml (privacy, terms).
 */
class PageController extends AbstractController
{
    public function privacy(): Response
    {
        return $this->render('pages/privacy.html.twig');
    }

    public function terms(): Response
    {
        return $this->render('pages/terms.html.twig');
    }
}
