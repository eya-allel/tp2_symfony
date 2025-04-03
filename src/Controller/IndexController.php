<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class IndexController extends AbstractController
{
    #[Route('/', name: 'homepage')]
public function home(): Response
{
    return $this->render('index.html.twig');
}
#[Route('/{name}', name: 'hello_name')]
public function hello(string $name): Response
{
    return $this->render('index.html.twig', [
        'name' => $name
    ]);
}
}