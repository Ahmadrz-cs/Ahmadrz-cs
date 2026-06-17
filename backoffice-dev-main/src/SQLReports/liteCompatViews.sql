/* SQLite compatible variant of ViewSetup for tests fixtures */

DROP VIEW IF EXISTS getInvestmentPayouts;
CREATE VIEW getInvestmentPayouts
as
SELECT 
    inv.id AS 'investment_id',
    ofr.id AS 'offering_id',
    ast.id AS 'asset_id',
    ast.name AS 'asset_name',
    inv.user_id AS 'investor_id',
    inv.investmentValue AS 'investment_value',
    inv.numberOfShares AS 'no_of_shares',
    inv.orgPricePerShare AS 'org_price_per_share',
    inv.PricePerShare AS 'price_per_share',
    inv.share_amount,
    inv.createdAt AS 'investment_created_at',
    inv.createdBy AS 'investment_created_by',
    invstatus.lifecycleStatus AS 'investment_status',
    py.dueDate,
    py.payoutAmount,
    py.fee,
    py.createdAt AS 'payout_created_at',
    py.createdBy AS 'payout_created_by',
    py.transactionId AS 'payout_transaction_id'
FROM investments inv
    INNER JOIN investments_status invstatus ON inv.investmentStatus_id = invstatus.id
    LEFT JOIN payouts py ON inv.id = py.investment_id
    LEFT JOIN offerings ofr ON inv.off_id = ofr.id
    LEFT JOIN assets ast ON ofr.asset_id = ast.id
;

DROP VIEW IF EXISTS getOfferingData;
CREATE VIEW getOfferingData
as
 SELECT offs.id,
    offs.asset_id,
    offs.inv_id,
    offs.offeringType,
    offs.name,
    offs.additionalType,
    offs.category,
    offs.fundingGoal,
    offs.externalCommitments,
    offs.isFeatured,
    offs.isSecondaryMrkt,
    offs.valuation,
    offs.equityOffered,
    offs.noOfShares,
    offs.pricePerShare,
    offs.netRentProjected,
    offs.grossRentProjected,
    offs.grossProjectReturn,
    offs.offeringTerm,
    offs.openDate,
    offs.closeDate,
    offs.minCommitUser,
    offs.maxCommitUser,
    offs.maxOverFunding,
    offs.primaryOfferingId,
    offs.comments,
    offs.visibility,
    offs.currency,
    offs.createdById,
    offs.createdAt,
    offs.updatedAt,
    offs.createdBy,
    offs.updatedBy,
    offs.offeringStatus_id,
	offstat.draftedOn,
    offstat.isDraft,
    offstat.archivedOn,
    offstat.isArchived,
    offstat.cancelledOn,
    offstat.isCancelled,
    offstat.submittedOn,
    offstat.isSubmitted,
    offstat.rejectedOn,
    offstat.isRejected,
    offstat.approvedOn,
    offstat.isApproved,
    offstat.publishedOn,
    offstat.isPublished,
    offstat.restrictedOn,
    offstat.isRestricted,
    offstat.closedOn,
    offstat.isClosed,
    offstat.settledOn,
    offstat.isSettled,
    offstat.lifecycleStatus,
    offstat.createdById as 'status.createdById',
    offstat.createdAt as 'status.createdAt',
    offstat.updatedAt as 'status.updatedAt',
    offstat.createdBy as 'status.createdBy',
    offstat.updatedBy as 'status.updatedBy'
FROM offerings offs
inner join offerings_status offstat on offs.offeringStatus_id = offstat.id ;

DROP VIEW IF EXISTS getAssetData;
CREATE VIEW getAssetData
as
SELECT
	asst.id,
    asst.name,
    asst.additionalType,
    asst.alternateName,
    asst.briefDescription,
    asst.companyNumber,
    asst.detailedDesc,
    asst.displayName,
    asst.legalName,
    asst.orgEmail,
    asst.sector,
    asst.taxId,
    asst.telephone,
    asst.fundingGoal,
    asst.amountOfShares,
    asst.setupFee,
    asst.adminFee,
    asst.managementFee,
    asst.profitShare,
    asst.stampDutyUser,
    asst.assetType,
    asst.investmentTerm,
    asst.grossRentalReturnPA,
    asst.netRentalReturnPA,
    asst.grossCapitalAppreciation,
    asst.netCapitalAppreciation,
    asst.netCapitalAppreciationYield,
    asst.gross_yield,
    asst.pointsOfInterest,
    asst.buyRestricted,
    asst.sellRestricted,
    asst.pricePerShare,
    asst.visibility,
    asst.mangoPayUserId,
    asst.mangoPayWalletId,
    asst.additional_wallet,
    asst.createdById,
    asst.createdAt,
    asst.updatedAt,
    asst.createdBy,
    asst.updatedBy,
    asst.contactPoint_id,
    asst.assetStatus_id,
    asstus.draftOn,
    asstus.isDraft,
    asstus.archivedOn,
    asstus.isArchived,
    asstus.cancelledOn,
    asstus.isCancelled,
    asstus.submittedOn,
    asstus.isSubmitted,
    asstus.rejectedOn,
    asstus.isRejected,
    asstus.publishedOn,
    asstus.isPublished,
    asstus.lifecycleStatus,
    asstus.createdById as 'status.createdById',
    asstus.createdAt as 'status.createdAt',
    asstus.updatedAt as 'status.updatedAt',
    asstus.createdBy as 'status.createdBy',
    asstus.updatedBy as 'status.updatedBy'
from
 assets asst
inner join assets_status asstus on asst.assetStatus_id = asstus.id;

DROP VIEW IF EXISTS getUserData;
CREATE VIEW getUserData
as
SELECT
usr.id,
usr.username,
usr.email,
usr.enabled,
usr.last_login,
usr.createdAt as 'joinDate',
usr.firstname,
usr.lastname,
usr.gender,
usr.type,
usr.middlename,
usr.honoricPrefix,
usr.honoricSuffix,
usr.company_id,
usr.jobTitle,
usr.location,
addr.address1,
addr.address2,
addr.address3,
addr.city,
addr.region,
addr.postCode,
addr.country,
usr.nationality,
usr.mobile,
usr.phone1,
usr.phone2,
usr.birthCountry,
usr.birthDate,
usr.birthPlace,
usr.drivingLicenseNo,
usr.passportNumber,
usr.passportCountry,
usr.passportExpiry,
usr.incomeRange,
usr.mangoPayUserId,
usr.mangoPayWalletId,
usr.isVIP,
usr.occupation,
usr.additionalType,
usr.additionalName,
usr.affiliateCode,
usr.biography,
usr.externalReferenceId,
usr.referralCode,
usr.sector,
usr.tagline,
usr.taxId,
usr.timezone,
usr.website,
usr.term_service_accepted,
usr.gdpr_accepted,
usr.createdAt,
usr.updatedAt,
usri.cxbWorthInvestor,
usri.cxbSophisticatedInvestor,
usri.cxbRestrictedUser,
usri.cxbLtdCompInvestor,
usri.alwaysGoUp,
usri.incomeEveryMonth,
usri.neverExit,
usri.poiFileId,
usri.wordsOfOwn,
usri.corporateInvestor,
usris.isEmailValidated,
usris.isKycApproved,
usris.isApproved,
usris.isRegCompleted,
usris.isBlocked,
usrcf.salesforceId,
usrcf.question3,
usrcf.referralLink,
usrcf.fatca,
COALESCE(usrcf.companyApprovedOn, 0) AS 'companyApprovedOn',
usrcf.questionnaire_passed,
usrcf.questionnaire_attempts
from users usr
inner join users_statuses usris on usr.status_id = usris.id
inner join  user_investors usri on usr.investor_id = usri.id
left join addresses addr on usr.id = addr.user_id
left join 
(
  select 
    user_id,
    max(if(fieldKey = 'salesforce_id', fieldValue, NULL)) as salesforceId,
    max(if(fieldKey = 'question3', fieldValue, NULL)) as question3,
    max(if(fieldKey = 'referral_link', fieldValue, NULL)) as referralLink,
    max(if(fieldKey = 'fatca', fieldValue, NULL)) as fatca,
    max(if(fieldKey = 'companyApprovedOn', fieldValue, NULL)) as companyApprovedOn,
    max(if(fieldKey = 'questionnaire_passed', fieldValue, NULL)) as questionnaire_passed,
    max(if(fieldKey = 'questionnaire_attempts', fieldValue, NULL)) as questionnaire_attempts
  from
    user_cus_fields
  group by 
    user_id
) as usrcf on usr.id = usrcf.user_id;

DROP VIEW IF EXISTS getHoldingData;
CREATE VIEW getHoldingData
as
SELECT hld.id,
    hld.asset_id,
    assts.name,
    hld.user_id,
    usr.email,
    hld.investment_id,
    inv.share_amount as 'investmest_shareamount',
    inv.name as 'investment_name',
	hld.transaction_id,
    hld.share_amount,
    hld.createdById,
    hld.createdAt,
    hld.updatedAt,
    hld.createdBy,
    hld.updatedBy
FROM holdings hld
INNER JOIN assets assts on hld.asset_id = assts.id
INNER JOIN users usr on hld.user_id = usr.id
LEFT JOIN investments inv on hld.investment_id = inv.id;

DROP VIEW IF EXISTS getTransactionData;
CREATE VIEW getTransactionData
as
SELECT id,
    user_id,
    currency,
    payment_status,
    createdById,
    createdAt,
    updatedAt,
    createdBy,
    updatedBy,
    inv_id,
    creditor_id,
    debitor_id,
    debited_wallet_id,
    credited_wallet_id,
    offering_id,
    share_amount,
    value_amount,
    fee_amount,
    trans_type,
    comments,
    external_id
FROM transactions;

DROP VIEW IF EXISTS getContegoLog;
CREATE VIEW getContegoLog
as
SELECT id,
    profile_name,
    rag,
    kyc_score,
    kyc_type,
    ext_reference_id,
    pdf_report_url,
    user,
    createdAt
FROM contego_logs;

DROP VIEW IF EXISTS getDivestments;
CREATE VIEW getDivestments
as
SELECT 
    inv_id, 
    SUM(investmentValue) AS 'divested_amount', 
    SUM(share_amount) AS 'divested_shares',
    COUNT(inv_id) AS 'divestment_trades'
FROM offerings offs
LEFT JOIN investments inv ON inv.off_id = offs.id
LEFT JOIN investments_status invstat ON inv.investmentStatus_id = invstat.id
WHERE 
    offs.inv_id IS NOT NULL 
    AND 
    invstat.isSettled = 1
GROUP BY inv_id
;

DROP VIEW IF EXISTS getCapitalRepayments;
CREATE VIEW getCapitalRepayments
AS
SELECT investment_id AS inv_id,
    MAX(IF(fieldKey = 'capitalRepaid', fieldValue, NULL)) AS 'capitalRepaid'
    FROM investment_add_fields
GROUP BY investment_id;

DROP VIEW IF EXISTS prefundingRetention;
CREATE VIEW prefundingRetention
AS
SELECT investment_id AS inv_id,
    MAX(IF(fieldKey = 'prefundingId', fieldValue, NULL)) AS 'prefundingId'
    FROM investment_add_fields
GROUP BY investment_id;

DROP VIEW IF EXISTS getInvestmentData;
CREATE VIEW getInvestmentData
as
SELECT inv.id,
    inv.user_id,
    inv.off_id,
    offer.name as 'Offering Name',
    inv.visibility,
    inv.for_sale,
    inv.name,
    inv.investmentValue,
    inv.numberOfShares,
    inv.currency,
    inv.interestRate,
    inv.term,
    inv.orgPricePerShare,
    inv.PricePerShare,
    inv.share_amount,
    inv.transaction_id,
    inv.type,
    inv.comments,
    inv.createdById,
    inv.createdAt,
    inv.updatedAt,
    inv.createdBy,
    inv.updatedBy,
    inv.investmentStatus_id,
	invst.openOn,
    invst.isOpen,
    invst.rejectedOn,
    invst.isRejected,
    invst.approvedOn,
    invst.isApproved,
    invst.withdrawnOn,
    invst.isWithdrawn,
    invst.settledOn,
    invst.isSettled,
    invst.lifecycleStatus,
    usr.referralCode as 'signedUpWithRefCode',
    investReff.referralEmail as 'referersUsername',
    invst.createdById as 'status.createdById',
    invst.createdAt as 'status.createdAt',
    invst.updatedAt as 'status.updatedAt',
    invst.createdBy as 'status.createdBy',
    invst.updatedBy as 'status.updatedBy',
    invcp.capitalRepaid AS 'Capital Repaid',
    invpr.prefundingId AS 'isRetention',
    usrinv.cxbWorthInvestor,
    usrinv.cxbSophisticatedInvestor,
    usrinv.cxbRestrictedUser,
    usrinv.corporateInvestor
FROM investments inv
inner join investments_status invst on inv.investmentStatus_id = invst.id
left join users usr on inv.user_id = usr.id
left join user_investors usrinv on inv.user_id = usrinv.id
left join offerings offer on inv.off_id = offer.id
LEFT JOIN getCapitalRepayments invcp ON inv.id = invcp.inv_id
LEFT JOIN prefundingRetention invpr ON inv.id = invpr.inv_id
left join
(
SELECT
        max(if(fieldKey = 'referral_link', fieldValue, NULL)) as referralLink,
        createdBy as referralEmail
    FROM
        user_cus_fields
    GROUP BY 
        createdBy
)   as investReff on usr.referralcode = investReff.referralLink;

DROP VIEW IF EXISTS getShareRegister;
CREATE VIEW getShareRegister
as
SELECT
    inv.id,
    CONCAT("SR", inv.id) AS 'Share No.',
    inv.type AS 'Investment Type',
    a.companyNumber AS 'SPV No.',
    ubuy.id AS 'Account No.',
    ubuy.email AS 'User Email',
    ubuy.username AS 'Username',
    ubuy.honoricPrefix AS 'Title', 
    ubuy.firstName,
    ubuy.lastName,
    ubuy.address1,
    ubuy.address2,
    ubuy.city,
    ubuy.region,
    ubuy.postCode,
    ubuy.country,
    ubuy.createdAt AS 'User Registered',
    a.name AS 'Asset Name',
    a_addr.address2 AS 'Asset Street Address',
    a_addr.city AS 'Asset City',
    a_addr.region AS 'Asset Region',
    a_addr.postCode AS 'Asset Postcode',
    a_addr.country AS 'Asset Country',
    inv.share_amount AS 'Number Of Shares',
    a.pricePerShare,
    ROUND(inv.share_amount*a.pricePerShare, 2) AS 'Share Capital',
    usell.id AS 'Seller Profile Id',
    usell.username AS 'Seller Username',
    usell.email AS 'Seller Email',
    usell.honoricPrefix AS 'Seller Title',
    usell.firstname AS 'Seller Firstname',
    usell.lastname AS 'Seller Lastname',
    usell.address2 AS 'Seller Address1',
    usell.city AS 'Seller City',
    usell.region AS 'Seller Region',
    usell.postCode AS 'Seller Postcode',
    usell.country AS 'Seller Country',
    inv.createdAt,
    inv.settledOn,
    inv.investmentValue AS 'Original Investment',
    ROUND(coalesce(d.divested_amount,0),2) AS 'Amount Divested',
    d.divested_shares AS 'Shares Divested',
    ROUND(inv.investmentValue-coalesce(d.divested_amount,0),2) AS 'Current Holding',
    inv.share_amount-coalesce(d.divested_shares,0) AS 'Current Share Holding',
    t.debited_wallet_id AS 'MP Debited Wallet Id',
    t.credited_wallet_id AS 'MP Credited Wallet Id',
    t.external_id AS 'Transaction Id',
    uc.name AS 'User Company Name',
    uc.registrationNumber AS 'User Company Registration',
    uc.regAddress1 AS 'User Company Reg Address1',
    uc.postCode AS 'User Company PostCode',
    COALESCE(ubuy.companyApprovedOn, 0) AS 'User Company Approved On',
    invcp.capitalRepaid AS 'Capital Repaid',
    invpr.prefundingId AS 'isRetention',
    usrinv.cxbWorthInvestor,
    usrinv.cxbSophisticatedInvestor,
    usrinv.cxbRestrictedUser,
    usrinv.corporateInvestor
FROM getInvestmentData inv
LEFT JOIN offerings o ON inv.off_id = o.id
LEFT JOIN assets a ON o.asset_id = a.id
LEFT JOIN asset_addresses a_addr ON a.id = a_addr.asset_id
LEFT JOIN transactions t ON inv.transaction_id = t.external_id
LEFT JOIN getUserData ubuy ON inv.user_id = ubuy.id
LEFT JOIN companies uc ON ubuy.company_id = uc.id
LEFT JOIN getUserData usell ON o.createdById = usell.id
LEFT JOIN getDivestments d ON inv.id = d.inv_id
LEFT JOIN getCapitalRepayments invcp ON inv.id = invcp.inv_id
LEFT JOIN prefundingRetention invpr ON inv.id = invpr.inv_id
LEFT JOIN user_investors usrinv on inv.user_id = usrinv.id
WHERE inv.isSettled=1
GROUP BY inv.id
;

DROP VIEW IF EXISTS getShareHoldings;
CREATE VIEW getShareHoldings
AS
SELECT 
    ast.name AS 'asset',
    ast.id AS 'assetId',
    usr.username AS 'user',
    usr.id AS 'userId',
    SUM(
        inv.share_amount 
        - COALESCE(invdvst.divested_shares, 0) 
        - COALESCE(invcp.capitalRepaid, 0) 
        - inv.extraSharesDivested 
    ) AS 'currentHolding',
    SUM(inv.share_amount) AS 'originalHolding',
    SUM(COALESCE(invdvst.divested_shares, 0) + inv.extraSharesDivested) AS 'divestedHolding',
    SUM(COALESCE(invdvst.divestment_trades, 0)) AS 'divestmentTrades',
    SUM(COALESCE(invcp.capitalRepaid, 0)) AS 'capitalRepayments'
FROM investments inv
    LEFT JOIN investments_status invstat ON inv.investmentStatus_id = invstat.id
    LEFT JOIN offerings ofr ON inv.off_id = ofr.id
    LEFT JOIN assets ast ON ofr.asset_id = ast.id
    LEFT JOIN users usr ON inv.user_id = usr.id
    LEFT JOIN getDivestments invdvst ON inv.id = invdvst.inv_id
    LEFT JOIN getCapitalRepayments invcp ON inv.id = invcp.inv_id
WHERE invstat.isSettled = 1
GROUP BY 
    ast.name,
    usr.id
;

DROP VIEW IF EXISTS getShareTrades;
CREATE VIEW getShareTrades
AS
SELECT 
    inv.id AS 'investment',
    ast.name AS 'asset',
    usrb.username AS 'buyer',
    CASE
        WHEN usrsd.username IS NOT NULL THEN usrsd.username
        ELSE usrs.username
    END AS 'seller',
    inv.share_amount AS 'numberOfShares',
    inv.createdAt AS 'investedOn',
    invstat.settledOn AS 'settledOn',
    ast.id AS 'assetId',
    usrb.id AS 'buyerId',
    CASE
        WHEN usrsd.id IS NOT NULL THEN usrsd.id
        ELSE usrs.id
    END AS 'sellerId'
FROM investments inv
    LEFT JOIN investments_status invstat ON inv.investmentStatus_id = invstat.id
    LEFT JOIN offerings ofr ON inv.off_id = ofr.id
    LEFT JOIN assets ast ON ofr.asset_id = ast.id
    LEFT JOIN users usrb ON inv.user_id = usrb.id
    LEFT JOIN investments invr ON ofr.inv_id = invr.id
    LEFT JOIN users usrs ON ofr.createdBy = usrs.username
    LEFT JOIN users usrsd ON invr.user_id = usrsd.id
WHERE invstat.isSettled = 1
;
