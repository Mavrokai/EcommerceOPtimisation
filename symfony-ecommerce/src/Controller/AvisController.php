<?php

namespace App\Controller;
use App\Entity\Product;
use App\Entity\Review;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/avis')]
final class AvisController extends AbstractController
{
    #[Route('', methods: ['POST'])]
public function create(Request $request, EntityManagerInterface $em): JsonResponse
{
    $data = json_decode($request->getContent(), true);

    if (!isset($data['productId'], $data['userId'], $data['rating'], $data['comment'])) {
        return $this->json(['error' => 'Invalid data'], 400);
    }

    $product = $em->getRepository(Product::class)->find($data['productId']);
    $user = $em->getRepository(User::class)->find($data['userId']);

    if (!$product || !$user) {
        return $this->json(['error' => 'Product or User not found'], 404);
    }

    $review = new Review();
    $review->setProduct($product);
    $review->setUser($user);
    $review->setRating($data['rating']);
    $review->setComment($data['comment']);
    $review->setCreatedAt(new \DateTime());
    $em->persist($review);
    $em->flush();

    return $this->json(['message' => 'Review created!', 'id' => $review->getId()], 201);
}

    #[Route('/{id}', methods: ['GET'])]
    public function show(Review $review): JsonResponse
    {
              return $this->json([
            'id' => $review->getId(),
            'rating' => $review->getRating(),
            'comment' => $review->getComment(),
            'createdAt' => $review->getCreatedAt()->format('Y-m-d H:i:s'),
            'user' => [
                'id' => $review->getUser()->getId(),
                'username' => $review->getUser()->getUsername(),
            ],
            'productId' => $review->getProduct()->getId(),
        ]); 
        }
    #[Route('/{id}', methods: ['PUT'])]
    public function update(Request $request, Review $review, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $review->setRating($data['rating'] ?? $review->getRating());
        $review->setComment($data['comment'] ?? $review->getComment());

        $em->flush();

        return $this->json(['message' => 'Review updated!']);
    }
    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(Review $review, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($review);
        $em->flush();

        return $this->json(['message' => 'Review deleted!']);
    }
}
