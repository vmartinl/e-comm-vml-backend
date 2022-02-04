<?php

namespace App\Controller;

use App\Entity\Cart;
use App\Entity\CartLine;
use App\Entity\Product;
use App\Repository\CartLineRepository;
use App\Repository\CartRepository;
use App\Repository\ProductRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Message;
use Symfony\Component\Mime\Part\TextPart;
use Symfony\Component\Routing\Annotation\Route;


class CartController extends AbstractController
{
    /**
     * @Route("/cart", name="cart")
     *
     * @param CartRepository $cartRepository
     * @param Session $session
     *
     * @return Response
     */
    public function index(CartRepository $cartRepository, Session $session): Response
    {
        $cart = $this->getCurrentCart($cartRepository, $session);

        return $this->json([
            'cart' => $cart instanceof Cart ? $cart : [],
        ]);
    }

    /**
     * @Route(
     *     "/cart/{productId}/{quantity}",
     *     name="app_cart_update_quantity",
     *     requirements={"quantity"="\d+", "productId"="\d+"},
     *     methods={"POST"}
     * )
     *
     * @param CartRepository $cartRepository
     * @param CartLineRepository $cartLineRepository
     * @param ProductRepository $productRepository
     * @param Session $session
     * @param ManagerRegistry $managerRegistry
     * @param int $productId
     * @param int $quantity
     *
     * @return Response
     */
    public function addToCart(CartRepository $cartRepository, CartLineRepository $cartLineRepository, ProductRepository $productRepository, Session $session, ManagerRegistry $managerRegistry, int $productId, int $quantity): Response
    {
        $cart = $this->getCurrentCart($cartRepository, $session);
        if (!$cart instanceof Cart) {
            $this->createNotFoundException('Empty cart');
        }

        $cartLine = $cartLineRepository->findOneByProductAndCart($productId, $cart);
        if ($cartLine instanceof CartLine) {
            $this->createNotFoundException('Product already in cart');
        }

        $product = $productRepository->find($productId);
        if (!$product instanceof Product) {
            $this->createNotFoundException('Product does not exist');
        }

        $cartLine = new CartLine();
        $cartLine
            ->setProduct($product)
            ->setQuantity($quantity);
        $managerRegistry->getManager()->flush();

        return $this->json(['cart' => $cart]);
    }

    /**
     * @Route(
     *     "/cart/{productId}/{quantity}",
     *     name="app_cart_update_quantity",
     *     requirements={"quantity"="\d+", "productId"="\d+"},
     *     methods={"PATCH"}
     * )
     *
     * @param CartRepository $cartRepository
     * @param CartLineRepository $cartLineRepository
     * @param Session $session
     * @param ManagerRegistry $managerRegistry
     * @param int $productId
     * @param int $quantity
     *
     * @return Response
     */
    public function updateQuantity(CartRepository $cartRepository, CartLineRepository $cartLineRepository, Session $session, ManagerRegistry $managerRegistry, int $productId, int $quantity): Response
    {
        $cart = $this->getCurrentCart($cartRepository, $session);
        if (!$cart instanceof Cart) {
            $this->createNotFoundException('Empty cart');
        }

        $cartLine = $cartLineRepository->findOneByProductAndCart($productId, $cart);
        if (!$cartLine instanceof CartLine) {
            $this->createNotFoundException('Product not in cart');
        }

        $cartLine->setQuantity($quantity);
        $managerRegistry->getManager()->flush();

        return $this->json(['cart' => $cart]);
    }

    /**
     * @Route(
     *     "/cart/{productId}",
     *     name="app_cart_remove_line",
     *     requirements={"productId"="\d+"},
     *     methods={"DELETE"}
     * )
     *
     * @param CartRepository $cartRepository
     * @param CartLineRepository $cartLineRepository
     * @param Session $session
     * @param ManagerRegistry $managerRegistry
     * @param int $productId
     *
     * @return Response
     */
    public function removeProduct(CartRepository $cartRepository, CartLineRepository $cartLineRepository, Session $session, ManagerRegistry $managerRegistry, int $productId): Response
    {
        $cart = $this->getCurrentCart($cartRepository, $session);
        if (!$cart instanceof Cart) {
            $this->createNotFoundException('Empty cart');
        }

        $cartLine = $cartLineRepository->findOneByProductAndCart($productId, $cart);
        if (!$cartLine instanceof CartLine) {
            $this->createNotFoundException('Product not in cart');
        }
        $cart->removeCartLine($cartLine);
        $managerRegistry->getManager()->flush();

        return $this->json(['cart' => $cart]);
    }

    /**
     * @Route("/cart/confirm", name="cart_confirm", methods={"POST"})
     *
     * @param CartRepository $cartRepository
     * @param Session $session
     * @param Mailer $mailer
     *
     * @return Response
     */
    public function confirm(CartRepository $cartRepository, Session $session, Mailer $mailer)
    {
        $cart = $this->getCurrentCart($cartRepository, $session);

        if ($cart instanceof Cart) {
            $message = new Message();
            $message->setBody(new TextPart('Panier confirmé !'));
            $envelope = new Envelope(new Address('no-reply@e-comm.vml'), [new Address($this->getUser()->getUserIdentifier())]);
            $mailer->send($message, $envelope);

            return new Response(Response::HTTP_OK);
        }

        $this->createNotFoundException('Empty cart');
    }

    /**
     * @param CartRepository $cartRepository
     * @param Session $session
     *
     * @return Cart|null
     */
    protected function getCurrentCart(CartRepository $cartRepository, Session $session): ?Cart
    {
        return ($this->isGranted('IS_AUTHENTICATED_FULLY')) ?
            $cartRepository->findOneByUser($this->getUser()) :
            $cartRepository->findOneBySession($session->getId());
    }
}
