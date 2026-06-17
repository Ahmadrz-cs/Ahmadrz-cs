<?php

namespace App\Controller\Admin;

use App\Entity\AssessmentResponse;
use App\Entity\Enum\QuestionType;
use App\Entity\User;
use App\Entity\UserAssessment;
use App\Entity\UserCategorisation;
use App\Form\QueryUserAssessmentType;
use App\Form\QueryUserCategorisationType;
use App\Form\Type\AssessmentResponseType;
use App\Form\Type\QueryUserType;
use App\Form\UserAssessmentType;
use App\Form\UserCategorisationType;
use App\Repository\AssessmentResponseRepository;
use App\Repository\UserAssessmentRepository;
use App\Repository\UserCategorisationRepository;
use App\Repository\UserRepository;
use App\Service\AssessmentService;
use App\Service\Util\ExportHelper;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sonata\Exporter\Exporter;
use Sonata\Exporter\Source\DoctrineORMQuerySourceIterator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/onboarding')]
#[IsGranted('ROLE_ANALYST')]
class OnboardingController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger,
        private EntityManagerInterface $entityManager,
        private Exporter $exporter,
    ) {}

    #[Route(path: '', name: 'admin_onboarding_index', methods: ['GET'])]
    public function index(Request $request, UserRepository $userRepository): Response
    {
        $form = $this->createForm(QueryUserType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $filters = $form->getData();
        }
        $results = $userRepository->findByWithAssociations(
            $filters ?? [],
            [$filters['orderBy'] ?? 'id' => $filters['orderDirection'] ?? 'DESC'],
            $filters['perPage'] ?? 10,
            $filters['page'] ?? 1,
        );
        return $this->render('admin/pages/onboarding/index.html.twig', [
            'objects' => $results,
            'form' => $form->createView(),
        ]);
    }

    #[Route(
        path: '/categorisations',
        name: 'admin_onboarding_categorisation_index',
        methods: ['GET'],
    )]
    public function categorisations(
        Request $request,
        UserCategorisationRepository $userCategorisationRepository,
    ): Response {
        $form = $this->createForm(QueryUserCategorisationType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $filters = $form->getData();
        }
        $results = $userCategorisationRepository->findByWithAssociations(
            $filters ?? [],
            [$filters['orderBy'] ?? 'id' => $filters['orderDirection'] ?? 'DESC'],
            $filters['perPage'] ?? 10,
            $filters['page'] ?? 1,
        );
        return $this->render('admin/pages/onboarding/categorisations/index.html.twig', [
            'objects' => $results,
            'form' => $form->createView(),
            'categoryCounts' => $userCategorisationRepository->getCountByCategory(),
        ]);
    }

    #[Route(
        path: '/categorisations/export',
        name: 'admin_onboarding_categorisation_export',
    )]
    public function exportUserCategorisations(
        Request $request,
        UserCategorisationRepository $userCategorisationRepository,
    ): StreamedResponse {
        $this->logger->debug('Export user categorisations');
        $query = $userCategorisationRepository->findAllWithJoins();
        $format = ExportHelper::validateExportFormat($request->query->get(
            'format',
            'csv',
        ));
        $fields = $this->entityManager
            ->getClassMetadata(UserCategorisation::class)
            ->getFieldNames();
        // Deal with enum for the category
        $categoryEnumIndex = array_search('category', $fields);
        if ($categoryEnumIndex) {
            $fields[$categoryEnumIndex] = 'category.value';
        }
        array_push(
            $fields,
            'profile.user.id',
            'profile.categoryReviewedAt',
            'profile.user.userIdentifier',
        );
        return $this->exporter->getResponse(
            $format,
            ExportHelper::generateFileName('user_categorisations_', $format),
            new DoctrineORMQuerySourceIterator($query, $fields),
        );
    }

    #[Route(
        path: '/assessments',
        name: 'admin_onboarding_assessment_index',
        methods: ['GET'],
    )]
    public function assessments(
        Request $request,
        UserAssessmentRepository $userAssessmentRepository,
    ): Response {
        $form = $this->createForm(QueryUserAssessmentType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $filters = $form->getData();
        }
        $results = $userAssessmentRepository->findByWithAssociations(
            $filters ?? [],
            [$filters['orderBy'] ?? 'id' => $filters['orderDirection'] ?? 'DESC'],
            $filters['perPage'] ?? 10,
            $filters['page'] ?? 1,
        );
        return $this->render('admin/pages/onboarding/assessments/index.html.twig', [
            'objects' => $results,
            'form' => $form->createView(),
            'attemptsAndOutcomes' => $userAssessmentRepository->getAttemptsAndOutcome(),
        ]);
    }

    #[Route(
        path: '/assessments/questions',
        name: 'admin_onboarding_assessment_questions',
        methods: ['GET'],
    )]
    public function assessmentQuestions(AssessmentService $assessmentService): Response
    {
        $questionsByArea = $assessmentService->findAndgroupQuestionsByArea(null);
        return $this->render('admin/pages/onboarding/assessments/questions.html.twig', [
            'questionsByArea' => $questionsByArea,
            'questionArea' => $assessmentService->getQuestionAreaMap(),
        ]);
    }

    #[Route(
        path: '/assessments/set/{set}',
        name: 'admin_onboarding_assessment_set',
        methods: ['GET'],
    )]
    public function assessmentSet(
        Request $request,
        int $set,
        AssessmentService $assessmentService,
    ): Response {
        $assessmentLength = $request->query->get('length', 0);
        $questionsByArea = $assessmentService->findAndgroupQuestionsByArea();
        $questions = $assessmentService->generateAssessment($set, $assessmentLength);
        $questionChoiceCounts = [];
        foreach ($questions as $q) {
            $questionChoiceCounts[$q->getId()] =
                $assessmentService->getQuestionChoiceCounts($q);
        }
        return $this->render('admin/pages/onboarding/assessments/generate_set.html.twig', [
            'questions' => $questions,
            'questionsByArea' => $questionsByArea,
            'questionChoiceCounts' => $questionChoiceCounts,
        ]);
    }

    #[Route(path: '/assessments/export', name: 'admin_onboarding_assessment_export')]
    public function exportUserAssessments(
        Request $request,
        UserAssessmentRepository $userAssessmentRepository,
    ): StreamedResponse {
        $this->logger->debug('Export user assessments');
        $query = $userAssessmentRepository->findAllWithJoins();
        $format = ExportHelper::validateExportFormat($request->query->get(
            'format',
            'csv',
        ));
        $fields = $this->entityManager
            ->getClassMetadata(UserAssessment::class)
            ->getFieldNames();
        array_push(
            $fields,
            'profile.user.id',
            'profile.assessmentPassed',
            'profile.user.userIdentifier',
        );
        return $this->exporter->getResponse(
            $format,
            ExportHelper::generateFileName('user_assessments_', $format),
            new DoctrineORMQuerySourceIterator($query, $fields),
        );
    }

    #[Route(
        path: '/assessments/responses/export',
        name: 'admin_onboarding_assessment_response_export',
    )]
    public function exportAssessmentResponses(
        Request $request,
        AssessmentResponseRepository $assessmentResponseRepository,
    ): StreamedResponse {
        $this->logger->debug('Export assessment responses');
        $query = $assessmentResponseRepository->findAllWithJoins();
        $format = ExportHelper::validateExportFormat($request->query->get(
            'format',
            'csv',
        ));
        $fields = $this->entityManager
            ->getClassMetadata(AssessmentResponse::class)
            ->getFieldNames();
        array_push(
            $fields,
            'assessment.id',
            'question.id',
            'question.section.name',
            'choice.id',
        );
        $fields['is_correct'] = 'choice.correct';
        return $this->exporter->getResponse(
            $format,
            ExportHelper::generateFileName('assessment_responses_', $format),
            new DoctrineORMQuerySourceIterator($query, $fields),
        );
    }

    #[Route(
        path: '/categorisations/{id}',
        name: 'admin_onboarding_categorisation_edit',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('ROLE_OPERATIONS')]
    public function editCategorisation(
        Request $request,
        UserCategorisation $categorisation,
    ): Response {
        $user = $categorisation->getProfile()->getUser();
        $form = $this->createForm(UserCategorisationType::class, $categorisation);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($categorisation->isVerified()) {
                $categorisation->setVerifiedBy($this->getUser());
            }
            if (is_null($categorisation->isVerified())) {
                $categorisation->setVerifiedBy(null);
            }
            $this->entityManager->flush();
            return $this->redirectToRoute(
                'admin_onboarding_profile',
                ['id' => $user->getId()],
                Response::HTTP_SEE_OTHER,
            );
        }
        return $this->render('admin/pages/onboarding/categorisations/edit.html.twig', [
            'categorisation' => $categorisation,
            'form' => $form,
        ]);
    }

    #[Route(
        path: '/assessments/{id}',
        name: 'admin_onboarding_assessment_edit',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('ROLE_OPERATIONS')]
    public function editAssessment(
        Request $request,
        UserAssessment $assessment,
    ): Response {
        $user = $assessment->getProfile()->getUser();
        $form = $this->createForm(UserAssessmentType::class, $assessment);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();
            return $this->redirectToRoute(
                'admin_onboarding_profile',
                ['id' => $user->getId()],
                Response::HTTP_SEE_OTHER,
            );
        }

        return $this->render('admin/pages/onboarding/assessments/edit.html.twig', [
            'assessment' => $assessment,
            'form' => $form,
        ]);
    }

    #[Route(
        path: '/assessments/{id}/simulate',
        name: 'admin_onboarding_assessment_simulate',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('ROLE_OPERATIONS')]
    public function simulateAssessment(
        Request $request,
        UserAssessment $assessment,
        AssessmentService $assessmentService,
    ): Response {
        $question = $assessmentService->getNextQuestion($assessment);
        if (is_null($question)) {
            $this->addFlash('success', 'All assessment areas completed');
            $assessment->setComplete(true);
            $assessmentService->markAssessment($assessment);
            $this->entityManager->flush();
        } else {
            $response = new AssessmentResponse();
            $response->setQuestion($question);
            $assessment->addResponse($response);
            $form = $this->createForm(AssessmentResponseType::class, $response, [
                'question' => $question ?? null,
            ]);
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $this->entityManager->persist($response);
                $this->entityManager->flush();
                return $this->redirectToRoute(
                    'admin_onboarding_assessment_simulate',
                    ['id' => $assessment->getId()],
                    Response::HTTP_SEE_OTHER,
                );
            }
            return $this->render('admin/pages/onboarding/assessments/simulate.html.twig', [
                'assessment' => $assessment,
                'question' => $question,
                'form' => $form,
            ]);
        }
        return $this->redirectToRoute(
            'admin_onboarding_assessment_edit',
            ['id' => $assessment->getId()],
            Response::HTTP_SEE_OTHER,
        );
    }

    #[Route(
        path: '/response/{id}',
        name: 'admin_onboarding_assessment_response',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('ROLE_OPERATIONS')]
    public function assessmentResponse(
        Request $request,
        AssessmentResponse $response,
    ): Response {
        $form = $this->createForm(AssessmentResponseType::class, $response, [
            'question' => $response->getQuestion(),
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($response);
            $this->entityManager->flush();
            return $this->redirectToRoute(
                'admin_onboarding_assessment_edit',
                ['id' => $response->getAssessment()->getId()],
                Response::HTTP_SEE_OTHER,
            );
        }
        return $this->render('admin/pages/onboarding/assessments/simulate.html.twig', [
            'assessment' => $response->getAssessment(),
            'question' => $response->getQuestion(),
            'form' => $form,
        ]);
    }

    #[Route(
        path: '/{id}/add-categorisation',
        name: 'admin_onboarding_categorisation_add',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('ROLE_OPERATIONS')]
    public function addCategorisation(Request $request, User $user): Response
    {
        $categorisation = new UserCategorisation();
        $user->getOnboardingProfile()->addCategorisation($categorisation);
        $form = $this->createForm(UserCategorisationType::class, $categorisation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->getOnboardingProfile()->setCategoryReviewedAt(new \DateTime());
            $user->getOnboardingProfile()->setCategory($categorisation->getCategory());
            if ($categorisation->isVerified()) {
                $categorisation->setVerifiedBy($this->getUser());
            }
            $this->entityManager->persist($categorisation);
            $this->entityManager->flush();
            return $this->redirectToRoute(
                'admin_onboarding_profile',
                ['id' => $user->getId()],
                Response::HTTP_SEE_OTHER,
            );
        }
        return $this->render('admin/pages/onboarding/categorisations/new.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route(
        path: '/{id}/add-assessment',
        name: 'admin_onboarding_assessment_add',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('ROLE_OPERATIONS')]
    public function addAssessment(Request $request, User $user): Response
    {
        $assessment = new UserAssessment(QuestionType::Appropriateness);
        $user->getOnboardingProfile()->addAssessment($assessment);
        $form = $this->createForm(UserAssessmentType::class, $assessment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($assessment);
            $this->entityManager->flush();
            return $this->redirectToRoute(
                'admin_onboarding_profile',
                ['id' => $user->getId()],
                Response::HTTP_SEE_OTHER,
            );
        }

        return $this->render('admin/pages/onboarding/assessments/new.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route(path: '/{id}', name: 'admin_onboarding_profile', methods: ['GET'])]
    public function profile(Request $request, User $user): Response
    {
        return $this->render('admin/pages/onboarding/profile.html.twig', [
            'user' => $user,
        ]);
    }
}
