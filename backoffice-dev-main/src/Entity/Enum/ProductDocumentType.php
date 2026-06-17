<?php

namespace App\Entity\Enum;

enum ProductDocumentType: string
{
    case Logo = 'logo';
    case ArticlesOfAssociation = 'articles_of_association';
    case InformationMemorandum = 'information_memorandum';
    case FinancialSummary = 'financial_summary';
    case PropertyPhotos = 'property_photos';
}
