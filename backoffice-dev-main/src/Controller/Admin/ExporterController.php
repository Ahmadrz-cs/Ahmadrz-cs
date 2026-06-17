<?php

namespace App\Controller\Admin;

use App\Entity\Enum\ExportReportType;
use App\Form\Type\ExportReportCustomiserType;
use App\Service\ExportService;
use App\Service\Util\ExportHelper;
use Psr\Log\LoggerInterface;
use Sonata\Exporter\Exporter;
use Sonata\Exporter\Source\ArraySourceIterator;
use Sonata\Exporter\Source\DoctrineORMQuerySourceIterator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/export')]
class ExporterController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger,
        private Exporter $exporter,
        private ExportService $exportService,
    ) {}

    #[Route(path: '/hub', name: 'admin_export_hub')]
    public function hub(): Response
    {
        $reportsAvailable = $this->exportService->getAvailableCustomReports();
        return $this->render('admin/pages/exports/builder_overview.html.twig', [
            'reportsAvailable' => $reportsAvailable,
        ]);
    }

    #[Route(path: '/builder/{report}', name: 'admin_export_report_builder')]
    public function reportBuilder(Request $request, string $report): Response
    {
        if (!$this->exportService->isSupportedReport($report)) {
            $this->addFlash('warning', 'Unknown report ' . $report);
            return $this->redirectToRoute('admin_export_hub');
        }
        $fields = $this->exportService->getFieldNames($report);
        // the user id is available across a number of different aliases, only some are supported
        if (!$fields) {
            $this->addFlash(
                'warning',
                "The $report report either has no data to export",
            );
            return $this->redirectToRoute('admin_export_hub');
        }
        $reportConfig = [
            'reportFields' => $request->query->get('clear') ? [] : $fields,
        ];
        $form = $this->createForm(ExportReportCustomiserType::class, $reportConfig, [
            'fieldChoices' => $fields,
            'userFields' => ExportService::SUPPORTED_USER_FIELDS,
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $reportConfig = $form->getData();
            $source = $this->exportService->getReportData(
                $report,
                $reportConfig['reportFields'],
                $reportConfig,
            );
            $format = $reportConfig['exportFormat'];
            return $this->exporter->getResponse(
                $format,
                ExportHelper::generateFileName(str_replace(' ', '', $report), $format),
                new ArraySourceIterator($source),
            );
        }
        return $this->render('admin/pages/exports/report_builder.html.twig', [
            'form' => $form->createView(),
            'supportedFields' => $fields,
            'supportedUserFields' => ExportService::SUPPORTED_USER_FIELDS,
            'reportSpecificFilters' => [],
        ]);
    }

    #[Route(path: '/orm-builder/{report}', name: 'admin_export_report_orm_builder')]
    public function ormReportBuilder(
        Request $request,
        ExportReportType $report,
    ): Response {
        $fields = $this->exportService->getOrmFieldNames($report);
        $reportConfig = [
            'reportFields' => $request->query->get('clear') ? [] : array_keys($fields),
        ];
        $reportSpecificFilters =
            $this->exportService->getReportSpecificFilters($report);
        // Set any defaults specified in $reportSpecificFilters
        $reportConfig = array_merge(
            $reportConfig,
            ...array_values($reportSpecificFilters),
        );
        $this->logger->debug('ReportConfig', $reportConfig);
        $form = $this->createForm(ExportReportCustomiserType::class, $reportConfig, [
            'fieldChoices' => array_keys($fields),
            'userFields' => ExportService::SUPPORTED_USER_FIELDS,
            'reportSpecificFilters' => array_keys($reportSpecificFilters),
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $reportConfig = $form->getData();
            $reportFields = $reportConfig['reportFields'];
            $query = $this->exportService->getOrmQuery($report, $reportConfig);
            $format = $reportConfig['exportFormat'];
            $fields = array_filter(
                $fields,
                fn(string $field): bool => \in_array($field, $reportFields),
                ARRAY_FILTER_USE_KEY,
            );
            return $this->exporter->getResponse(
                $format,
                ExportHelper::generateFileName(
                    str_replace(' ', '', $report->value) . '_',
                    $format,
                ),
                new DoctrineORMQuerySourceIterator(
                    query: $query,
                    fields: $fields,
                    dateTimeFormat: \DateTimeInterface::ATOM,
                ),
            );
        }
        return $this->render('admin/pages/exports/report_builder.html.twig', [
            'form' => $form->createView(),
            'supportedFields' => array_keys($fields),
            'supportedUserFields' => ExportService::SUPPORTED_USER_FIELDS,
            'reportSpecificFilters' => array_keys($reportSpecificFilters),
        ]);
    }

    #[Route(path: '/download/{report}', name: 'admin_export_download_report')]
    public function downloadReport(Request $request, string $report): Response
    {
        $source = $this->exportService->getReportData($report);
        $format = ExportHelper::validateExportFormat($request->query->get(
            'format',
            'csv',
        ));

        if (!$this->exportService->isSupportedReport($report)) {
            $this->logger->notice('not found');
            $this->addFlash('warning', 'Unknown report ' . $report);
            return $this->redirectToRoute('admin_export_hub');
        }

        return $this->exporter->getResponse(
            $format,
            ExportHelper::generateFileName(str_replace(' ', '', $report), $format),
            new ArraySourceIterator($source),
        );
    }
}
