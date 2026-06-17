<?php

namespace App\Controller\Admin;

use App\Entity\Enum\QuestionArea;
use App\Entity\Question;
use App\Entity\QuestionChoice;
use App\Form\Type\QuestionChoiceType;
use App\Form\Type\QuestionType;
use App\Repository\QuestionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/questions')]
#[IsGranted('ROLE_ANALYST')]
class QuestionController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger,
        private RequestStack $requestStack,
    ) {}

    #[Route('', name: 'admin_question_index', methods: ['GET'])]
    public function index(QuestionRepository $questionRepository): Response
    {
        // Experimental way to reset the "exit" path back to the CMS area
        $this->requestStack->getSession()->set('questionBuilderMode', false);
        return $this->render('admin/pages/question/index.html.twig', [
            'questions' => $questionRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'admin_question_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_OPERATIONS')]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response {
        $question = new Question();
        if (
            $request->query->get('area')
            && !is_null(QuestionArea::tryFrom($request->query->get('area')))
        ) {
            $question->setSection(QuestionArea::from($request->query->get('area')));
        }
        // Experimental way to set the "exit" path to the Onboarding Hub rather than the CMS area
        if ($request->query->get('builderMode')) {
            $this->requestStack->getSession()->set('questionBuilderMode', true);
        }
        $form = $this->createForm(QuestionType::class, $question);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($question);
            $entityManager->flush();

            return $this->redirectToRoute(
                'admin_question_show',
                ['id' => $question->getId()],
                Response::HTTP_SEE_OTHER,
            );
        }

        return $this->render('admin/pages/question/new.html.twig', [
            'question' => $question,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_question_show', methods: ['GET'])]
    public function show(Question $question): Response
    {
        return $this->render('admin/pages/question/show.html.twig', [
            'question' => $question,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_question_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_OPERATIONS')]
    public function edit(
        Request $request,
        Question $question,
        EntityManagerInterface $entityManager,
    ): Response {
        $form = $this->createForm(QuestionType::class, $question);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute(
                'admin_question_show',
                ['id' => $question->getId()],
                Response::HTTP_SEE_OTHER,
            );
        }

        return $this->render('admin/pages/question/edit.html.twig', [
            'question' => $question,
            'form' => $form,
        ]);
    }

    #[Route(
        '/{id}/add-choice',
        name: 'admin_question_add_choice',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('ROLE_OPERATIONS')]
    public function addChoice(
        Request $request,
        Question $question,
        EntityManagerInterface $entityManager,
    ): Response {
        $questionChoice = new QuestionChoice();
        $question->addChoice($questionChoice);
        $form = $this->createForm(QuestionChoiceType::class, $questionChoice);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($questionChoice);
            $entityManager->flush();

            return $this->redirectToRoute(
                'admin_question_show',
                ['id' => $question->getId()],
                Response::HTTP_SEE_OTHER,
            );
        }

        return $this->render('admin/pages/question_choice/new.html.twig', [
            'question_choice' => $questionChoice,
            'form' => $form,
        ]);
    }

    // #[Route('/{id}', name: 'admin_question_delete', methods: ['POST'])]
    // public function delete(Request $request, Question $question, EntityManagerInterface $entityManager): Response
    // {
    //     if ($this->isCsrfTokenValid('delete' . $question->getId(), $request->getPayload()->getString('_token'))) {
    //         $entityManager->remove($question);
    //         $entityManager->flush();
    //     }
    //     return $this->redirectToRoute('admin_question_index', [], Response::HTTP_SEE_OTHER);
    // }
}
