<?php

namespace App\Controller;

use App\Entity\Book;
use App\Repository\BookRepository;
use App\Repository\AuthorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class BookController extends AbstractController
{

    /**
     * @Route("/api/books", name="app_book", methods= {"GET"})
     */
    public function getAllBooks(BookRepository $bookRepository, SerializerInterface $serializer): JsonResponse
    {
        $bookList = $bookRepository->findAll();
        
        $jsonBookList = $serializer->serialize($bookList, 'json', ['groups' => 'getBooks']);
        return new JsonResponse($jsonBookList, Response::HTTP_OK, [], true);
    }

    /**
     * @Route ("/api/books/{id}", name= "detailBook", methods= {"GET"})
     */
    public function getDetailBook(Book $book, SerializerInterface $serializer): JsonResponse
    {
        if ($book) {
            $jsonBook = $serializer->serialize($book, 'json', ['groups' => 'getBooks']);
            return new JsonResponse($jsonBook, Response::HTTP_OK, [], true);
        }
    }

    /**
     * @Route ("/api/books/{id}", name= "deleteBook", methods= {"DELETE"})
     */
    public function deleteBook(Book $book, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($book);
        $em->flush();
        
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @Route ("/api/books", name= "createdBook", methods= {"POST"})
     * @IsGranted ("ROLE_ADMIN", message= "Vous n'avez pas les droits pour créer in livre")
     */
    public function createdBook(Request $request, SerializerInterface $serializer, EntityManagerInterface $em, 
        UrlGeneratorInterface $urlGenerator, AuthorRepository $authorRepository, ValidatorInterface $validator): JsonResponse
    {
        $book = $serializer->deserialize($request->getContent(), Book::class, 'json');

        $errors = $validator->validate($book);
        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        $em->persist($book);
        $em->flush();

        $content = $request->toArray();
        $idAuthor = $content['idAuthor'] ?? -1;

        $book->setAuthor($authorRepository->find($idAuthor));

        $location = $urlGenerator->generate('detailBook', ['id' => $book->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        $jsonBook = $serializer->serialize($book, 'json', ['groups' => 'getBooks']);
        return new JsonResponse($jsonBook, Response::HTTP_CREATED, ['Location' => $location], true);
    }

    /**
     * @Route ("/api/books/{id}", name= "updateBook", methods= {"PUT"})
     */
    public function updateBook(Book $CurrentBook, Request $request, SerializerInterface $serializer, EntityManagerInterface $em, 
                        UrlGeneratorInterface $urlGenerator, AuthorRepository $authorRepository, ValidatorInterface $validator): JsonResponse
    {
        $updatedBook = $serializer->deserialize($request->getContent(), Book::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $CurrentBook]);

        $content = $request->toArray();
        $idAuthor = $content['idAuthor'] ?? -1;

        $updatedBook->setAuthor($authorRepository->find($idAuthor));

        $errors = $validator->validate($updatedBook);
        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        $em->persist($updatedBook);
        $em->flush();

        $location = $urlGenerator->generate('detailBook', ['id' => $updatedBook->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        
        $jsonUpdateBook = $serializer->serialize($updatedBook, 'json', ['groups' => 'getBooks']);
        return new JsonResponse($jsonUpdateBook, Response::HTTP_CREATED, ['Location' => $location], true);
    }
}
