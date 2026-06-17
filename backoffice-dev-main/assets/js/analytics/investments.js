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
    counts[item.date] = item.count;
    totals[item.date] = item.total;
  }
  console.log(dataSet);
  console.log(Object.values(counts));

  const labels = Object.keys(counts);
  const data = {
    labels: labels,
    datasets: [
      {
        type: 'bar',
        label: 'Investment count over time',
        data: Object.values(counts),
        backgroundColor: 'rgba(108, 188, 182, 0.4)',
        borderColor: 'rgba(108, 188, 182, 1)',
        borderWidth: 2,
        yAxisID: 'y',
      },
      {
        label: 'Investment values over time (£)',
        data: Object.values(totals),
        backgroundColor: 'rgba(52, 58, 64, 0.8)',
        borderColor: 'rgba(52, 58, 64, 0.1)',
        borderWidth: 2,
        yAxisID: 'y1',
      },
    ]
  };
  const config = {
    type: 'line',
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
          text: 'Investments & Investment Values Over Time'
        }
      },
      scales: {
        y: {
          type: 'linear',
          display: true,
          position: 'left',
        },
        y1: {
          type: 'linear',
          display: true,
          position: 'right',
          grid: {
            drawOnChartArea: false,
          },
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

fetchJSON('/admin/analytics/investments?data=1');