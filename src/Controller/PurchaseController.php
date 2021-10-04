<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Purchase;
use App\Cart\CartService;
use App\Form\CartConfirmationType;
use App\Purchase\PurchasePersister;
use App\Repository\PurchaseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class PurchaseController extends AbstractController
{
    protected $cartService;
    protected $em;
    protected $persister;

    public function __construct(CartService $cartService, EntityManagerInterface $em, PurchasePersister $persister)
    {
        $this->cartService = $cartService;
        $this->em = $em;
        $this->persister = $persister;
    }

    #[Route('/profile/purchases', name: 'purchase_list')]
    public function list()
    {
        /** @var User */
        $user = $this->getUser();

        return $this->render('purchase/list.html.twig', [
            'purchases' => $user->getPurchases(),
        ]);
    }


    // NUMERO 2
    #[Route("profile/purchase/confirm", name: "purchase_confirm")]
    public function confirm(Request $request)
    {
        //
        $form = $this->createForm(CartConfirmationType::class);

        $form->handleRequest($request);

        if (!$form->isSubmitted()) {
            $this->addFlash('danger', 'Attention vous n\'avez pas remplie le formulaire');
            return $this->redirectToRoute('cart_show');
        }

        //
        $user = $this->getUser();

        //
        $cartItems = $this->cartService->getDetailedCart();

        if (count($cartItems) === 0) {
            $this->addFlash('warning', 'Vous ne pouvez pas confirmer une commande avec un panier vide');
            return $this->redirectToRoute('cart_show');
        }

        /** @var Purchase */
        $purchase = $form->getData();

        $this->persister->storePurchase($purchase);

        return $this->redirectToRoute('purchase_list');
    }
}
