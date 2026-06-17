<?php

namespace App\Controller\ApiV3;

use App\Dto\User\UserQueryDto;
use App\Dto\User\UserRequestDto;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\Mapper\UserMapper;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class UserController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger,
        private UserMapper $userMapper,
        private EntityManagerInterface $em,
        private UserRepository $userRepository,
    ) {}

    #[IsGranted('ROLE_ADMIN')]
    #[Route(path: '/users', methods: ['GET'])]
    public function list(
        #[MapQueryString] UserQueryDto $dto,
        NormalizerInterface $normalizer,
    ): JsonResponse {
        $this->logger->debug('APIv3 list users');
        $filters = $normalizer->normalize($dto);
        // $this->logger->debug("Filters ", $filters);
        $users = $this->userRepository->findByWithAssociations($filters, [
            'id' => 'desc',
        ]);
        return $this->json($this->userMapper->mapMultipleToDto($users));
    }

    #[IsGranted('ROLE_USER')]
    #[Route(path: '/users/me', methods: ['GET'])]
    public function retrieveSelf(): JsonResponse
    {
        $this->logger->debug('APIv3 get user');
        return $this->json($this->userMapper->mapToDto($this->getUser()));
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route(path: '/users/{id}', methods: ['GET'])]
    public function retrieve(#[MapEntity(id: 'id')] User $user): JsonResponse
    {
        $this->logger->debug('APIv3 get user');
        return $this->json($this->userMapper->mapToDto($user));
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route(path: '/users', methods: ['POST'])]
    public function create(
        #[MapRequestPayload(validationGroups: ['create'])] UserRequestDto $dto,
    ): JsonResponse {
        $this->logger->debug('APIv3 create user');
        $user = $this->userMapper->mapToEntity($dto);
        $this->em->persist($user);
        $this->em->flush();
        return $this->json(
            data: $this->userMapper->mapToDto($user),
            status: Response::HTTP_CREATED,
        );
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route(path: '/users/{id}', methods: ['PATCH'])]
    public function update(
        #[MapEntity(id: 'id')] User $user,
        #[MapRequestPayload] UserRequestDto $dto,
    ): JsonResponse {
        $this->logger->debug('APIv3 update user');
        $user = $this->userMapper->mapToEntity($dto, $user);
        $this->em->flush();
        return $this->json($this->userMapper->mapToDto($user));
    }
}
