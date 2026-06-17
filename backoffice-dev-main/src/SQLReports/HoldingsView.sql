/* Subset - SQLite compatible variant of ViewSetup for tests fixtures */

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
GROUP BY inv_id;

DROP VIEW IF EXISTS getCapitalRepayments;
CREATE VIEW getCapitalRepayments
AS
SELECT investment_id AS inv_id,
    SUM(CASE
        WHEN fieldKey = 'capitalRepaid'
        THEN fieldValue
        ELSE 0
    END) AS 'capitalRepaid'
    FROM investment_add_fields
GROUP BY investment_id;

DROP VIEW IF EXISTS getHoldingData;
CREATE VIEW getHoldingData
AS
SELECT
    hld.id,
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

DROP VIEW IF EXISTS payoutsReport;
CREATE VIEW payoutsReport AS
SELECT py.id,
    py.payoutAmount,
    py.dueDate,
    py.payoutType,
    py.currency,
    py.fee,
    py.shareholding,
    py.createdAt,
    py.createdBy,
    py.updatedAt,
    py.updatedBy,
    usr.id AS creditedUserId,
    usr.username AS creditedUser,
    asset.id AS assetId,
    asset.companyNumber AS assetSpv,
    asset.name AS assetName,
    inv.id AS investmentId
FROM payouts py
    LEFT JOIN assets asset ON py.asset_id = asset.id
    LEFT JOIN users usr ON py.credited_user_id = usr.id
    LEFT JOIN investments inv ON py.investment_id = inv.id