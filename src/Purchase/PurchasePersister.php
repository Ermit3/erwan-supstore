<?php

namespace App\Purchase;

use App\Cart\CartService;
use DateTime;
use App\Entity\Purchase;
use App\Entity\PurchaseItem;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Security;

class PurchasePersister extends AbstractController
{
    protected $security;
    protected $cartService;
    protected $em;

    public function __construct(Security $security, CartService $cartService, EntityManagerInterface $em)
    {
        $this->security = $security;
        $this->cartService = $cartService;
        $this->em = $em;
    }

    public function storePurchase(Purchase $purchase)
    {
        $user = $this->security->getUser();

        $purchase->setUser($user)
            ->setCreatedAt(new DateTime());

        $this->em->persist($purchase);

        foreach ($this->cartService->getDetailedCart() as $cartItems) {
            $purchaseItem = new PurchaseItem;

            $purchaseItem->setPurchase($purchase)
                ->setProduct($cartItems->product)
                ->setProductName($cartItems->product->getName())
                ->setProductPrice($cartItems->product->getPrice())
                ->setQuantity($cartItems->qty)
                ->setTotal($cartItems->getTotal());

            $this->em->persist($purchaseItem);
        }
        $purchase->setTotal($purchaseItem->getTotal());
        $this->em->persist($purchase);
        $this->em->flush();
    }
}
