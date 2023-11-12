<?php

namespace App\Controller;

use App\Entity\Author;
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
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AuthorController extends AbstractController
{
    /**
     * @Route("/api/authors", name="app_author", methods={"GET"})
     */
    public function getAllauthors(AuthorRepository $authorRepository, SerializerInterface $serializer): JsonResponse
    {
        $authorList = $authorRepository->findAll();
        
        $jsonAuthorList = $serializer->serialize($authorList, 'json', ['groups' => 'getAuthors']);
        return new JsonResponse($jsonAuthorList, Response::HTTP_OK, [], true);
    }

    /**
     * @Route("/api/authors/{id}", name="detailAuthor", methods={"GET"})
     */
    public function getDetailAuthoruthors(Author $author, SerializerInterface $serializer): JsonResponse
    {
        if ($author) {
            $jsonAuthor = $serializer->serialize($author, 'json', ['groups' => 'getAuthors']);
            return new JsonResponse($jsonAuthor, Response::HTTP_OK, [], true);
        }
        
    }

    /**
     * @Route("/api/authors/{id}", name="deleteAuthor", methods={"DELETE"})
     */
    public function deleteAuthor(Author $author, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($author);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @Route("/api/authors", name="createdAuthor", methods={"POST"})
     */
    public function createdAuthor(Request $request, SerializerInterface $serializer, EntityManagerInterface $em, 
        UrlGeneratorInterface $urlGenerator, ValidatorInterface $validator): JsonResponse
    {
        $author = $serializer->deserialize($request->getContent(), Author::class, 'json');

        $errors = $validator->validate($author);
        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        $em->persist($author);
        $em->flush();

        $location = $urlGenerator->generate('detailAuthor', ['id' => $author->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        
        $jsonAuthor = $serializer->serialize($author, 'json', ['groups' => 'getAuthors']);
        return new JsonResponse($jsonAuthor, Response::HTTP_CREATED, ['Location' => $location], true);
    }

    /**
     * @Route ("/api/authors/{id}", name= "updateAuthor", methods= {"PUT"})
     */
    public function updateAuthor(Author $CurrentAuthor, Request $request, SerializerInterface $serializer, 
                        EntityManagerInterface $em, UrlGeneratorInterface $urlGenerator, ValidatorInterface $validator): JsonResponse
    {
        $updatedAuthor = $serializer->deserialize($request->getContent(), Author::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $CurrentAuthor]);

        $errors = $validator->validate($updatedAuthor);
        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        $em->persist($updatedAuthor);
        $em->flush();

        $location = $urlGenerator->generate('detailAuthor', ['id' => $updatedAuthor->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        
        $jsonUpdateAuthor = $serializer->serialize($updatedAuthor, 'json', ['groups' => 'getAuthors']);
        return new JsonResponse($jsonUpdateAuthor, Response::HTTP_CREATED, ['Location' => $location], true);
    }
}
