window.addEventListener('DOMContentLoaded', (event) => {
    updatePermissionsTable();
});

const permissionsForm = document.getElementsByName('user_permissions')[0];

permissionsForm.addEventListener('input', updatePermissionsTable);

function updatePermissionsTable() {
    let roleChoice = permissionsForm.elements['user_permissions[roles]'].value;
    superAdminList.forEach(element => updateElementContent(element, ''));
    // console.log(roleChoice);
    switch (roleChoice) {
        case 'ROLE_SUPER_ADMIN':
            superAdminList.forEach(element => updateElementContent(element));
            break;
        case 'ROLE_ADMIN':
            adminList.forEach(element => updateElementContent(element));
            break;
        case 'ROLE_OPERATIONS':
            operationsList.forEach(element => updateElementContent(element));
            break;
        case 'ROLE_ANALYST':
            analystList.forEach(element => updateElementContent(element));
            break;
        default:
            return;
    }
}

function updateElementContent(locator, content = '<i class="fa fa-check"></i>') {
    document.querySelector(locator).innerHTML = content;
}

const superAdminList = [
    '#read-user',
    '#update-user',
    '#read-user-status',
    '#update-user-status',
    '#create-user-document',
    '#read-user-document',
    '#update-user-document',
    '#delete-user-document',
    '#create-asset',
    '#read-asset',
    '#update-asset',
    '#create-asset-status',
    '#read-asset-status',
    '#update-asset-status',
    '#create-asset-document',
    '#read-asset-document',
    '#update-asset-document',
    '#delete-asset-document',
    '#create-offering',
    '#read-offering',
    '#update-offering',
    '#create-offering-status',
    '#read-offering-status',
    '#update-offering-status',
    '#create-offering-document',
    '#read-offering-document',
    '#update-offering-document',
    '#delete-offering-document',
    '#create-investment',
    '#read-investment',
    '#update-investment',
    '#create-investment-status',
    '#read-investment-status',
    '#update-investment-status',
    '#create-investment-document',
    '#read-investment-document',
    '#update-investment-document',
    '#delete-investment-document',
    '#read-payments-payouts',
    '#read-holdings',
    '#read-transaction-log',
    '#read-contego-log',
    '#read-other-change-logs',
    '#create-super-admin',
    '#read-super-admin',
    '#update-super-admin',
    '#delete-super-admin',
    '#create-admin',
    '#read-admin',
    '#update-admin',
    '#delete-admin',
    '#create-operations',
    '#read-operations',
    '#update-operations',
    '#delete-operations',
    '#create-analyst',
    '#read-analyst',
    '#update-analyst',
    '#delete-analyst'
];

const adminList = [
    '#read-user',
    '#update-user',
    '#read-user-status',
    '#update-user-status',
    '#create-user-document',
    '#read-user-document',
    '#update-user-document',
    '#delete-user-document',
    '#create-asset',
    '#read-asset',
    '#update-asset',
    '#create-asset-status',
    '#read-asset-status',
    '#update-asset-status',
    '#create-asset-document',
    '#read-asset-document',
    '#update-asset-document',
    '#delete-asset-document',
    '#create-offering',
    '#read-offering',
    '#update-offering',
    '#create-offering-status',
    '#read-offering-status',
    '#update-offering-status',
    '#create-offering-document',
    '#read-offering-document',
    '#update-offering-document',
    '#delete-offering-document',
    '#create-investment',
    '#read-investment',
    '#update-investment',
    '#create-investment-status',
    '#read-investment-status',
    '#update-investment-status',
    '#create-investment-document',
    '#read-investment-document',
    '#update-investment-document',
    '#delete-investment-document',
    '#read-payments-payouts',
    '#read-holdings',
    '#read-transaction-log',
    '#read-contego-log',
    '#read-other-change-logs',
    '#create-admin',
    '#read-admin',
    '#update-admin',
    '#delete-admin',
    '#create-operations',
    '#read-operations',
    '#update-operations',
    '#delete-operations',
    '#create-analyst',
    '#read-analyst',
    '#update-analyst',
    '#delete-analyst'
]

const operationsList = [
    '#read-user',
    '#update-user',
    '#read-user-status',
    '#update-user-status',
    '#create-user-document',
    '#read-user-document',
    '#update-user-document',
    '#create-asset',
    '#read-asset',
    '#update-asset',
    '#create-asset-status',
    '#read-asset-status',
    '#update-asset-status',
    '#create-asset-document',
    '#read-asset-document',
    '#update-asset-document',
    '#create-offering',
    '#read-offering',
    '#update-offering',
    '#create-offering-status',
    '#read-offering-status',
    '#update-offering-status',
    '#create-offering-document',
    '#read-offering-document',
    '#update-offering-document',
    '#create-investment',
    '#read-investment',
    '#update-investment',
    '#create-investment-status',
    '#read-investment-status',
    '#update-investment-status',
    '#create-investment-document',
    '#read-investment-document',
    '#update-investment-document',
    '#read-payments-payouts',
    '#read-holdings',
    '#read-transaction-log',
    '#read-contego-log',
    '#read-other-change-logs',
    '#read-operations',
    '#read-analyst'
]

const analystList = [
    '#read-user',
    '#read-user-status',
    '#read-user-document',
    '#read-asset',
    '#read-asset-status',
    '#read-asset-document',
    '#read-offering',
    '#read-offering-status',
    '#read-offering-document',
    '#read-investment',
    '#read-investment-status',
    '#read-investment-document',
    '#read-payments-payouts',
    '#read-holdings',
    '#read-transaction-log',
    '#read-contego-log',
    '#read-operations',
    '#read-analyst'
]