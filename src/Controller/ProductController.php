<?php

namespace App\Controller;

use App\Entity\Product;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ProductController extends AbstractController
{
    #[Route('/{category_slug}/{slug}', name: 'product_view', priority: -1)]
    public function index($slug, ProductRepository $productRepository): Response
    {
        $product = $productRepository->findOneBy([
            'slug' => $slug,
        ]);

        return $this->render('product/product_view.html.twig', [
            'controller_name' => 'ProductController',
            'product' => $product
        ]);
    }

    #[Route('/admin/product/create', name: 'product_create')]
    public function create(SluggerInterface $slugger, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ProductType::class, new Product);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $product = $form->getData();

            $product->setSlug(strtolower($slugger->slug($product->getName())));

            $em->persist($product);
            $em->flush();

            return $this->redirectToRoute('product_view', [
                'category_slug' => $product->getCategory()->getSlug(),
                'slug' => $product->getSlug(),
            ]);
        }

        $formView = $form->createView();

        return $this->render('product/product_create.html.twig', [
            'formView' => $formView,
        ]);
    }

    #[Route('/admin/product/{id}/edit', name: 'product_edit')]
    public function edit($id, SluggerInterface $slugger, ProductRepository $productRepository, Request $request, EntityManagerInterface $em): Response
    {
        $product = $productRepository->find($id);

        $form = $this->createForm(ProductType::class, $product);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $product->setSlug(strtolower($slugger->slug($product->getName())));
            $em->flush();

            return $this->redirectToRoute('product_view', [
                'category_slug' => $product->getCategory()->getSlug(),
                'slug' => $product->getSlug(),
            ]);
        }

        $formView = $form->createView();

        return $this->render('product/product_edit.html.twig', [
            'product' => $product,
            'formView' => $formView
        ]);
    }
}
