\! echo "Reporting starting database and table sizes";
SELECT table_schema AS "Database Name", 
ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS "Size in (MB)" 
FROM information_schema.TABLES 
GROUP BY table_schema;

SELECT table_name AS "Table",
ROUND(((data_length + index_length) / 1024 / 1024), 2) AS "Size (MB)"
FROM information_schema.TABLES
WHERE table_schema = DATABASE()
ORDER BY (data_length + index_length) DESC;

\! echo "Updating documentUrls to fixtures";
-- Default doc
UPDATE documents SET documentUrl = "fixtures/Test_PDF.pdf", filename = "Test_PDF.pdf";
-- Specific docs by tag
UPDATE documents SET documentUrl = "fixtures/male-passport.jpg" WHERE tag LIKE "proof%";
UPDATE documents SET documentUrl = "fixtures/share_certificate.jpg" WHERE tag LIKE "share%";
UPDATE documents SET documentUrl = "fixtures/Test_PDF.pdf" WHERE tag LIKE "calculations%";
UPDATE documents SET documentUrl = "fixtures/livingroomstock.jpg" WHERE tag = "property_photos";
UPDATE documents SET documentUrl = "fixtures/smallbedroomstock.jpg" WHERE tag = "floor_plan";
UPDATE documents SET documentUrl = "fixtures/camdenstock.jpg" WHERE tag = "logo";
UPDATE documents SET documentUrl = "fixtures/Test_PDF.pdf" WHERE tag = "read_to_activate";
UPDATE documents SET `name` = "staging-doc.pdf" WHERE `name` IS NOT NULL;

\! echo "Cleaning out logs";
DELETE FROM users_communications;
OPTIMIZE table users_communications;
DELETE FROM userLogs;
OPTIMIZE table userLogs;
DELETE FROM ext_log_entries;
OPTIMIZE table ext_log_entries;

\! echo "Updating wallet ids";
UPDATE users SET mangoPayUserId = 'user_m_01HW3EZ05T54PYM5E5RBDJJVHA', mangoPayWalletId = 'wlt_m_01HW3EZZB7JG7ZZ96VAKCSFMAQ';
UPDATE users SET  mangoPayUserId = 'user_m_01HW3CCXCYF1W0QNYE3KXVNRRQ', mangoPayWalletId = 'wlt_m_01HW3CMGAM5NJJ01RM8TQ6H8BS' WHERE username = 'irfan@yielders.co.uk';
UPDATE users SET  mangoPayUserId = 'user_m_01HW3CCXCYF1W0QNYE3KXVNRRQ', mangoPayWalletId = 'wlt_m_01HW3CMGAM5NJJ01RM8TQ6H8BS' WHERE username = 'admin@crowdtek.co.uk';
UPDATE users SET  mangoPayUserId = 'user_m_01HW3E0JWXCSNK856X9ZETKKPJ', mangoPayWalletId = 'wlt_m_01HW3E2FBKJ5R1QWKWR4K6ZETP' WHERE username = 'keesh@crowdtek.co.uk';
UPDATE assets SET  mangoPayWalletId = 'wlt_m_01HW3DD8S6MFPYGVC0FPBHXAF2', additional_wallet = 'wlt_m_01HW3DETE9YHAGN7GEGAH1PF65', depositWalletId = 'wlt_m_01HW3DH1PXVVGSP9634636PR48', expensesWalletId = 'wlt_m_01HW3DHG5E7750DXHJZK6JHMRA', taxWalletId = 'wlt_m_01HW3DJDVMWE4RBH8J1Z5359PK', distributionWalletId = 'wlt_m_01HW3DK5RNVTHJAFMQX34Q1WZD', treasuryWalletId = 'wlt_m_01HW3DKQ6D3Y5Z2AM717NBZD6V';
\! echo "Updating first party settlement order wallet ids";
-- hold
UPDATE transfer_request tr
    LEFT JOIN transfer_order tro ON tro.id = tr.transferOrder_id
SET tr.debitWalletId = 'wlt_m_01HW3DD8S6MFPYGVC0FPBHXAF2'
WHERE tro.transferType = 'investment settlement';
-- stamp duty
UPDATE transfer_request tr
    LEFT JOIN transfer_order tro ON tro.id = tr.transferOrder_id
SET tr.creditWalletId = 'wlt_m_01HW3EZZB7JG7ZZ96VAKCSFMAQ'
WHERE tro.transferType = 'investment settlement'
    AND tr.creditWalletId = '54654738';
-- settlement
UPDATE transfer_request tr
    LEFT JOIN transfer_order tro ON tro.id = tr.transferOrder_id
SET tr.creditWalletId = 'wlt_m_01HW3DETE9YHAGN7GEGAH1PF65'
WHERE tro.transferType = 'investment settlement'
    AND tr.creditWalletId != 'wlt_m_01HW3EZZB7JG7ZZ96VAKCSFMAQ';

\! echo "Toggle TOTP off";
UPDATE user_security SET mfaTotp = 0;

\! echo "Cleaning PII from kyc and investment records";
UPDATE contego_logs
    LEFT JOIN users usr ON contego_logs.user = usr.username
    SET user = CONCAT("stage", usr.id, "@test.yielderverse.co.uk")
    WHERE usr.username NOT LIKE '%yielders.co.uk' AND username NOT LIKE '%helpmewithit.com' AND username NOT LIKE '%crowdtek.co.uk';

UPDATE contego_logs SET pdf_report_url = "https://example.com" WHERE pdf_report_url LIKE 'https%';
UPDATE contego_score SET rule_messages = "staging-rule-message";
UPDATE kyc_report SET note = "https://example.com" WHERE note LIKE 'https%';
UPDATE investments SET `name` = "staging investment";

\! echo "Cleaning users and associated tables";
UPDATE users SET
    firstname = "Stage",
    lastname = "Test",
    middlename = NULL,
    referralCode = NULL,
    phone1 = "07000 111222",
    phone2 = NULL,
    birthDate = "1990-08-04",
    username = CONCAT("stage", id, "@test.yielderverse.co.uk"),
    username_canonical = CONCAT("stage", id, "@test.yielderverse.co.uk"),
    email = CONCAT("stage", id, "@test.yielderverse.co.uk"),
    email_canonical = CONCAT("stage", id, "@test.yielderverse.co.uk"),
    nationality = "GB"
WHERE username NOT LIKE '%yielders.co.uk' AND username NOT LIKE '%helpmewithit.com' AND username NOT LIKE '%crowdtek.co.uk';

UPDATE user_investors SET wordsOfOwn = "Example top yielder msg" WHERE wordsOfOwn IS NOT NULL;

UPDATE user_cus_fields SET fieldValue = "testsfidtest" WHERE fieldKey = "salesforce_id";
UPDATE user_cus_fields SET fieldValue = "testreflink" WHERE fieldKey = "referral_link";

\! echo "Cleaning (user) addresses table";
UPDATE addresses SET
    address1 = "123 Example Street",
    address2 = NULL,
    address3 = NULL,
    city = "London",
    region = NULL,
    postCode = "AB12 3CD",
    country = "GB",
    createdBy = "admin@test.yielderverse.co.uk",
    updatedBy = "admin@test.yielderverse.co.uk";

\! echo "Cleaning companies table";
UPDATE companies SET
    `name` = "Example company",
    position = "Director",
    regAddress1 = "456 Company Avenue",
    regAddress2 = NULL,
    regAddress3 = NULL,
    beneficialOwners = NULL,
    directors = NULL,
    regCountry = "GB",
    businessNature = "Finance",
    telephone = NULL,
    postCode = "AB12 3CD",
    buildingName = "Test building",
    registrationNumber = "12345678",
    otherName = NULL,
    companyWebsite = NULL,
    operatingAddress = "456 Company Avenue",
    operatingPostCode = "",
    createdBy = "admin@test.yielderverse.co.uk",
    updatedBy = "admin@test.yielderverse.co.uk"
WHERE createdBy IS NOT NULL OR updatedBy IS NOT NULL;

\! echo "Cleaning createdBy and updatedBy metadata";
UPDATE users_statuses SET createdBy = "admin@test.yielderverse.co.uk", updatedBy = "admin@test.yielderverse.co.uk";
UPDATE user_cus_fields SET createdBy = "admin@test.yielderverse.co.uk", updatedBy = "admin@test.yielderverse.co.uk";
UPDATE user_docs SET createdBy = "admin@test.yielderverse.co.uk", updatedBy = "admin@test.yielderverse.co.uk";
UPDATE user_investors SET createdBy = "admin@test.yielderverse.co.uk", updatedBy = "admin@test.yielderverse.co.uk";
UPDATE user_security SET createdBy = "admin@test.yielderverse.co.uk", updatedBy = "admin@test.yielderverse.co.uk";
UPDATE offerings  SET createdBy = "admin@test.yielderverse.co.uk", updatedBy = "admin@test.yielderverse.co.uk";
UPDATE offerings_status  SET createdBy = "admin@test.yielderverse.co.uk", updatedBy = "admin@test.yielderverse.co.uk";
UPDATE offering_docs SET createdBy = "admin@test.yielderverse.co.uk", updatedBy = "admin@test.yielderverse.co.uk";
UPDATE investments SET createdBy = "admin@test.yielderverse.co.uk", updatedBy = "admin@test.yielderverse.co.uk";
UPDATE investments_status SET createdBy = "admin@test.yielderverse.co.uk", updatedBy = "admin@test.yielderverse.co.uk";
UPDATE transactions SET createdBy = "admin@test.yielderverse.co.uk", updatedBy = "admin@test.yielderverse.co.uk";
UPDATE direct_debit SET createdBy = "admin@test.yielderverse.co.uk", updatedBy = "admin@test.yielderverse.co.uk";
UPDATE documents SET createdBy = "admin@test.yielderverse.co.uk", updatedBy = "admin@test.yielderverse.co.uk";
UPDATE contego_logs SET createdBy = "admin@test.yielderverse.co.uk", updatedBy = "admin@test.yielderverse.co.uk";
UPDATE contego_score SET createdBy = "admin@test.yielderverse.co.uk", updatedBy = "admin@test.yielderverse.co.uk";
UPDATE kyc_profile SET createdBy = "admin@test.yielderverse.co.uk", updatedBy = "admin@test.yielderverse.co.uk";
UPDATE bank_account SET createdBy = "admin@test.yielderverse.co.uk", updatedBy = "admin@test.yielderverse.co.uk";

UPDATE kyc_report SET createdBy = "admin@test.yielderverse.co.uk";

\! echo "Removing oauth2 activity";
DELETE FROM oauth2_access_token;
DELETE FROM oauth2_authorization_code;

\! echo "Removing redundant temporary tables";
DROP TABLE IF EXISTS xxxuser_cus_fieldsxxx, user_add_fields_tempxxx;

\! echo "Reporting ending database and table sizes";
SELECT table_schema AS "Database Name", 
ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS "Size in (MB)" 
FROM information_schema.TABLES 
GROUP BY table_schema;

SELECT table_name AS "Table",
ROUND(((data_length + index_length) / 1024 / 1024), 2) AS "Size (MB)"
FROM information_schema.TABLES
WHERE table_schema = DATABASE()
ORDER BY (data_length + index_length) DESC;

\! echo "Updating oauth2 clients - add dev client";
UPDATE oauth2_client SET redirectUris = "https://staging-front.yielderverse.co.uk/auth/callback" WHERE redirectUris IS NOT NULL; 
INSERT INTO `oauth2_client` (
    `name`,
    `secret`,
    `redirectUris`,
    `grants`,
    `scopes`,
    `active`,
    `allowPlainTextPkce`,
    `identifier`
  )
VALUES (
    'yielderverse',
    '71dcf8066c14b07a772a3c8af9217c318dc4385f9fa8215ab5eb11e511fd2cbda50714463088226b8ceefed0126482f2c36f2f4fe7ec4f5ba7c47935b02a9c8e',
    'http://front.dev.local/auth/callback http://front.dev.local:8001/auth/callback https://staging-front.yielderverse.co.uk/auth/callback https://rc-front.yielderverse.co.uk/auth/callback https://dev-front.yielderverse.co.uk/auth/callback http://localhost:3000/api/auth/callback/yielders http://127.0.0.1:3000/api/auth/callback/yielders',
    'client_credentials password refresh_token authorization_code',
    'asset:read asset:write offering:read offering:write investment:read investment:write payout:read payout:write user:read user:write',
    1,
    1,
    '904c1b4d9a15529ed70ff5e686345a9f'
  );
  
INSERT INTO `user_client` (
    `alias`,
    `description`,
    `createdAt`,
    `updatedAt`,
    `user_id`,
    `client_id`
  )
VALUES (
    'yielderverse',
    'Yielderverse development and test client',
    '2026-03-03 12:57:37',
    '2026-03-03 12:57:37',
    7,
    '904c1b4d9a15529ed70ff5e686345a9f'
  );