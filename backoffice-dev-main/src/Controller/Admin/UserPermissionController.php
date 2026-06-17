<?php

namespace App\Controller\Admin;

use App\Entity\RolePermissions;
use App\Entity\User;
use App\Form\Type\UserPermissionsType;
use App\Repository\UserRepository;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/users')]
#[IsGranted('ROLE_ANALYST')]
class UserPermissionController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger,
        private ManagerRegistry $doctrine,
        private UserRepository $userRepository,
        private array $roleHierarchyList,
    ) {}

    #[Route('/staff', name: 'admin_user_list_staff', methods: ['GET'])]
    public function viewUsers(Request $request): Response
    {
        // Minimum roles viewable by any admin user (analyst or higher)
        $roles = [
            'ROLE_TECH_OPS',
            'ROLE_FINANCIAL_OPS',
            'ROLE_OPERATIONS',
            'ROLE_ANALYST',
        ];
        if ($this->isGranted('ROLE_ADMIN')) {
            $roles[] = 'ROLE_ADMIN';
        }
        if ($this->isGranted('ROLE_SUPER_ADMIN')) {
            $roles[] = 'ROLE_SUPER_ADMIN';
        }

        $filters = [
            'perPage' => 10,
            'page' => $request->query->get('page', 1),
        ];
        $queryBuilder = $this->userRepository->buildUserWithAdminRolesQuery($roles);
        $adapter = new QueryAdapter($queryBuilder);
        $results = new Pagerfanta($adapter);
        $results->setMaxPerPage($filters['perPage'] ?? 10);
        $results->setCurrentPage(min($filters['page'] ?? 1, $results->getNbPages()));

        return $this->render('admin/pages/users/list_staff.html.twig', [
            'users' => $results,
        ]);
    }

    #[Route('/{user}/roles', name: 'admin_user_role_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function editRoles(Request $request, User $user): Response
    {
        $roles = $this->roleHierarchyList;
        $userRole = '';
        foreach (array_keys($roles) as $currentRole) {
            if ($user->hasRole($currentRole)) {
                $userRole = $currentRole;
                break;
            }
        }
        if (
            $user->hasRole('ROLE_SUPER_ADMIN') && !$this->isGranted('ROLE_SUPER_ADMIN')
        ) {
            $this->addFlash(
                'error',
                'Insufficient permissions. You may only edit users at the same or lower level role',
            );
            return $this->redirectToRoute('admin_user_list_staff');
        }
        $form = $this->createForm(UserPermissionsType::class, $user, [
            'super_admin' => $this->isGranted('ROLE_SUPER_ADMIN'),
            'role_types' => $roles,
            'user_role' => $userRole,
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            $form->getData();
            $this->doctrine->getManager()->flush();
            return $this->redirectToRoute('admin_user_list_staff');
        }

        return $this->render('admin/pages/users/edit_roles.html.twig', [
            'user' => $user,
            'permissionRoles' => RolePermissions::getAllPermissionRoles(),
            'form' => $form->createView(),
        ]);
    }
}
