<?php

namespace App\Controller;

use App\Cart\CartService;
use App\Form\CartConfirmationType;
use App\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class CartController extends AbstractController
{
    protected $productRepository;
    protected $cartService;

    public function __construct(CartService $cartService, ProductRepository $productRepository)
    {
        $this->productRepository = $productRepository;
        $this->cartService = $cartService;
    }

    #[Route('/cart', name: 'cart_show')]
    public function show()
    {
        $form = $this->createForm(CartConfirmationType::class);

        $detailledCart = $this->cartService->getDetailedCart();
        $total = $this->cartService->getTotal();

        return $this->render("cart/show.html.twig", [
            'items' => $detailledCart,
            'total' => $total,
            'confirmationForm' => $form->createView(),
        ]);
    }

    #[Route('/cart/add/{id}', name: 'cart_add', requirements: ['id' => "\d+"])]
    public function add($id, Request $request, ProductRepository $productRepository)
    {
        // Verification d'existance
        $product = $productRepository->find($id);
        if (!$product) {
            throw $this->createNotFoundException("Le product $id n'existe pas.");
        }

        $this->cartService->add($id);

        $this->addFlash("success", "Le produit à bien été rajouter au panier");

        return new RedirectResponse($request->headers->get('referer'));
    }

    #[Route('/cart/remove/{id}', name: 'cart_remove', requirements: ['id' => "\d+"])]
    public function remove($id, Request $request, ProductRepository $productRepository)
    {
        // Verification d'existance
        $product = $productRepository->find($id);
        if (!$product) {
            throw $this->createNotFoundException("Le product $id n'existe pas.");
        }

        $this->cartService->decrement($id);

        $this->addFlash("success", "Le produit a ete retirer");

        return new RedirectResponse($request->headers->get('referer'));
    }

    #[Route('/cart/delete/{id}', name: 'cart_delete', requirements: ['id' => "\d+"])]
    public function delete($id)
    {
        $product = $this->productRepository->find($id);

        if (!$product) {
            throw $this->createNotFoundException("Ce produit n'existe pas");
        }

        $this->cartService->remove($id);

        $this->addFlash("success", "Le produit a ete supprimer");

        return $this->redirectToRoute("cart_show");
    }
}
