<?php

namespace App\Controller\Admin;

use App\Entity\QuestionChoice;
use App\Form\Type\QuestionChoiceType;
use App\Repository\QuestionChoiceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/questions/choices')]
#[IsGranted('ROLE_ANALYST')]
class QuestionChoiceController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    // #[Route('/', name: 'admin_question_choice_index', methods: ['GET'])]
    // public function index(QuestionChoiceRepository $questionChoiceRepository): Response
    // {
    //     return $this->render('admin/pages/question_choice/index.html.twig', [
    //         'question_choices' => $questionChoiceRepository->findAll(),
    //     ]);
    // }

    // #[Route('/new', name: 'admin_question_choice_new', methods: ['GET', 'POST'])]
    // public function new(Request $request, EntityManagerInterface $entityManager): Response
    // {
    //     $questionChoice = new QuestionChoice();
    //     $form = $this->createForm(QuestionChoiceType::class, $questionChoice);
    //     $form->handleRequest($request);

    //     if ($form->isSubmitted() && $form->isValid()) {
    //         $entityManager->persist($questionChoice);
    //         $entityManager->flush();

    //         return $this->redirectToRoute('admin_question_choice_index', [], Response::HTTP_SEE_OTHER);
    //     }

    //     return $this->render('admin/pages/question_choice/new.html.twig', [
    //         'question_choice' => $questionChoice,
    //         'form' => $form,
    //     ]);
    // }

    // #[Route('/{id}', name: 'admin_question_choice_show', methods: ['GET'])]
    // public function show(QuestionChoice $questionChoice): Response
    // {
    //     return $this->render('admin/pages/question_choice/show.html.twig', [
    //         'question_choice' => $questionChoice,
    //     ]);
    // }

    #[Route('/{id}/edit', name: 'admin_question_choice_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_OPERATIONS')]
    public function edit(
        Request $request,
        QuestionChoice $questionChoice,
        EntityManagerInterface $entityManager,
    ): Response {
        $form = $this->createForm(QuestionChoiceType::class, $questionChoice);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute(
                'admin_question_show',
                ['id' => $questionChoice->getQuestion()->getId()],
                Response::HTTP_SEE_OTHER,
            );
        }

        return $this->render('admin/pages/question_choice/edit.html.twig', [
            'question_choice' => $questionChoice,
            'form' => $form,
        ]);
    }

    // #[Route('/{id}', name: 'admin_question_choice_delete', methods: ['POST'])]
    // public function delete(Request $request, QuestionChoice $questionChoice, EntityManagerInterface $entityManager): Response
    // {
    //     if ($this->isCsrfTokenValid('delete' . $questionChoice->getId(), $request->getPayload()->getString('_token'))) {
    //         $entityManager->remove($questionChoice);
    //         $entityManager->flush();
    //     }
    //     return $this->redirectToRoute('admin_question_choice_index', [], Response::HTTP_SEE_OTHER);
    // }
}
