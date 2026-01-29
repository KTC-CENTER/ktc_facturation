<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Form\UserPasswordType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/users')]
#[IsGranted('ROLE_ADMIN')]
class UserController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    #[Route('', name: 'app_user_index', methods: ['GET'])]
    public function index(Request $request, PaginatorInterface $paginator): Response
    {
        $search = $request->query->get('search', '');
        $role = $request->query->get('role', '');
        $showInactive = $request->query->getBoolean('inactive', false);

        $queryBuilder = $this->userRepository->createQueryBuilder('u');

        if (!$showInactive) {
            $queryBuilder->andWhere('u.isActive = :active')
                ->setParameter('active', true);
        }

        if ($search) {
            $queryBuilder->andWhere('u.email LIKE :search OR u.firstName LIKE :search OR u.lastName LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        if ($role && in_array($role, array_keys(User::ROLES))) {
            $queryBuilder->andWhere('u.roles LIKE :role')
                ->setParameter('role', '%' . $role . '%');
        }

        $queryBuilder->orderBy('u.lastName', 'ASC')
            ->addOrderBy('u.firstName', 'ASC');

        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            20
        );

        return $this->render('user/index.html.twig', [
            'users' => $pagination,
            'search' => $search,
            'role' => $role,
            'showInactive' => $showInactive,
            'roles' => User::ROLES,
        ]);
    }

    #[Route('/new', name: 'app_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user, [
            'require_password' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Hasher le mot de passe
            $plainPassword = $form->get('plainPassword')->getData();
            $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
            $user->setPassword($hashedPassword);

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $this->addFlash('success', 'Utilisateur créé avec succès.');
            return $this->redirectToRoute('app_user_show', ['id' => $user->getId()]);
        }

        return $this->render('user/new.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_user_show', methods: ['GET'])]
    public function show(User $user): Response
    {
        return $this->render('user/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user): Response
    {
        // Empêcher la modification d'un super admin par un admin
        if (in_array('ROLE_SUPER_ADMIN', $user->getRoles()) && !$this->isGranted('ROLE_SUPER_ADMIN')) {
            $this->addFlash('error', 'Vous ne pouvez pas modifier un Super Administrateur.');
            return $this->redirectToRoute('app_user_index');
        }

        $form = $this->createForm(UserType::class, $user, [
            'require_password' => false,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'Utilisateur modifié avec succès.');
            return $this->redirectToRoute('app_user_show', ['id' => $user->getId()]);
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/password', name: 'app_user_password', methods: ['GET', 'POST'])]
    public function changePassword(Request $request, User $user): Response
    {
        // Empêcher la modification d'un super admin par un admin
        if (in_array('ROLE_SUPER_ADMIN', $user->getRoles()) && !$this->isGranted('ROLE_SUPER_ADMIN')) {
            $this->addFlash('error', 'Vous ne pouvez pas modifier un Super Administrateur.');
            return $this->redirectToRoute('app_user_index');
        }

        if ($request->isMethod('POST')) {
            $newPassword = $request->request->get('new_password');
            $confirmPassword = $request->request->get('confirm_password');

            if ($newPassword !== $confirmPassword) {
                $this->addFlash('error', 'Les mots de passe ne correspondent pas.');
            } elseif (strlen($newPassword) < 8) {
                $this->addFlash('error', 'Le mot de passe doit contenir au moins 8 caractères.');
            } else {
                $hashedPassword = $this->passwordHasher->hashPassword($user, $newPassword);
                $user->setPassword($hashedPassword);
                $this->entityManager->flush();

                $this->addFlash('success', 'Mot de passe modifié avec succès.');
                return $this->redirectToRoute('app_user_show', ['id' => $user->getId()]);
            }
        }

        return $this->render('user/password.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}/toggle', name: 'app_user_toggle', methods: ['POST'])]
    public function toggle(Request $request, User $user): Response
    {
        // Empêcher la désactivation de soi-même
        if ($user === $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez pas vous désactiver vous-même.');
            return $this->redirectToRoute('app_user_index');
        }

        // Empêcher la désactivation d'un super admin par un admin
        if (in_array('ROLE_SUPER_ADMIN', $user->getRoles()) && !$this->isGranted('ROLE_SUPER_ADMIN')) {
            $this->addFlash('error', 'Vous ne pouvez pas désactiver un Super Administrateur.');
            return $this->redirectToRoute('app_user_index');
        }

        if ($this->isCsrfTokenValid('toggle' . $user->getId(), $request->request->get('_token'))) {
            $user->setIsActive(!$user->isActive());
            $this->entityManager->flush();

            $message = $user->isActive() ? 'Utilisateur activé.' : 'Utilisateur désactivé.';
            $this->addFlash('success', $message);
        }

        return $this->redirectToRoute('app_user_index');
    }

    #[Route('/{id}/delete', name: 'app_user_delete', methods: ['POST'])]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function delete(Request $request, User $user): Response
    {
        // Empêcher la suppression de soi-même
        if ($user === $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez pas vous supprimer vous-même.');
            return $this->redirectToRoute('app_user_index');
        }

        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->request->get('_token'))) {
            // Vérifier s'il y a des documents liés
            if ($user->getProformas()->count() > 0 || $user->getInvoices()->count() > 0) {
                $this->addFlash('error', 'Impossible de supprimer cet utilisateur car il a créé des documents. Désactivez-le plutôt.');
                return $this->redirectToRoute('app_user_show', ['id' => $user->getId()]);
            }

            $this->entityManager->remove($user);
            $this->entityManager->flush();

            $this->addFlash('success', 'Utilisateur supprimé.');
        }

        return $this->redirectToRoute('app_user_index');
    }
}
