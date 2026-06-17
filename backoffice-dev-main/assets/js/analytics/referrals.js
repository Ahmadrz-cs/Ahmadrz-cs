import Chart from 'chart.js/auto';

/**
 * Example based on
 * https://developers.google.com/web/ilt/pwa/working-with-the-fetch-api
 * 
 * Using the native fetch() API
 * 
 * Can also use Axios which provides are nicer interface
 * Axios also works server side as a node (JS) module
 * Thus giving the benefit of using the same syntax/interface on client and server side
 */

function logResult(result) {
    console.log(result);
}

function logError(error) {
    console.log('Looks like there was a problem: \n', error);
}

function validateResponse(response) {
    if (!response.ok) {
        throw Error(response.statusText);
    }
    return response;
}

function readResponseAsJSON(response) {
    return response.json();
}

function showChart(responseAsJSON) {
    const ctx = document.getElementById('chartArea');
    let dataSet = JSON.parse(ctx.dataset.chart);

    let counts = {};
    let totals = {};
    for (let item of dataSet) {
        counts[item.referralCode] = item.referralCodeCount;
        totals[item.referralCode] = item.investmentsValue;
    }
    console.log(dataSet);
    console.log(Object.values(counts));

    const labels = Object.keys(counts);
    const data = {
        labels: labels,
        datasets: [
            {
                label: 'Investments Count',
                data: Object.values(counts),
                backgroundColor: 'rgba(108, 188, 182, 0.4)',
                borderColor: 'rgba(108, 188, 182, 1)',
                borderWidth: 2,
                yAxisID: 'y',
            },
            {
                label: 'Investment Values (£)',
                data: Object.values(totals),
                backgroundColor: 'rgba(52, 58, 64, 0.8)',
                borderColor: 'rgba(52, 58, 64, 0.1)',
                borderWidth: 2,
                yAxisID: 'y2',
            }
        ]
    };
    const config = {
        type: 'bar',
        data: data,
        options: {
            responsive: true,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            stacked: false,
            plugins: {
                title: {
                    display: true,
                    text: 'Referral Investments Count & Total Investments Value'
                }
            },
            scales: {
                y: {
                    type: 'linear',
                    position: 'left',
                },
                y2: {
                    type: 'linear',
                    position: 'right',
                }
            }
        },
    };
    const chart = new Chart(ctx, config);
    // bit dodgy, but return the original data so it can be logged in the chain
    return responseAsJSON;
}

function fetchJSON(pathToResource) {
    fetch(pathToResource) // 1
        .then(validateResponse) // 2
        .then(readResponseAsJSON) // 3
        .then(showChart) // 4
        .then(logResult) // 5
        .catch(logError);
}

fetchJSON('/admin/analytics/referrals?data=1');