<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProductController extends AbstractController
{
    /**
     * @Route("/products", name="products", methods={"GET"})
     *
     * @param ProductRepository $productRepository
     *
     * @return Response
     */
    public function index(ProductRepository $productRepository): Response
    {
        $products = $productRepository->findAll();

        return $this->json([
            'products' => $products,
        ]);
    }

    /**
     * @Route("/products/{title}", name="products_search", methods={"GET"}, requirements={"title"="\s+"})
     *
     * @param ProductRepository $productRepository
     *
     * @return Response
     */
    public function findProducts(ProductRepository $productRepository, string $title): Response
    {
        $products = $productRepository->findWithTitleLike($title);
        if (empty($products)) {
            $this->createNotFoundException('No products like this');
        }

        return $this->json([
            'products' => $products,
        ]);
    }

    /**
     * @Route("/product/{id}", name="product", methods={"GET"}, requirements={"id"="\d+"})
     *
     * @param ProductRepository $productRepository
     *
     * @return Response
     */
    public function getProduct(ProductRepository $productRepository, int $id): Response
    {
        $product = $productRepository->find($id);
        if (!$product instanceof Product) {
            $this->createNotFoundException('No products like this');
        }

        return $this->json([
            'product' => $product,
        ]);
    }
}
