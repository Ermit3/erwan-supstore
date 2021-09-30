<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProductController extends AbstractController
{
    #[Route('/product/{slug}', name: 'product_view')]
    public function index($slug, ProductRepository $productRepository): Response
    {
        $product = $productRepository->findOneBy([
            'slug' => $slug,
        ]);

        return $this->render('product/index.html.twig', [
            'controller_name' => 'ProductController',
            'product' => $product
        ]);
    }
}
