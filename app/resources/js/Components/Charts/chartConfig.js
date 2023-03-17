export const accountGrowthLinesOptions = {
  responsive: true,

  tooltips: {
    intersect: false,
    /*
    callbacks: {
      label: function(tooltipItem, data) {
        const dataset = data.datasets[tooltipItem.datasetIndex];
        const index = tooltipItem.index;
        return dataset.labels[index] + ': $' + dataset.data[index];
      }
    }
    */
  },

  maintainAspectRatio: false,
  plugins:{
    title: {
      display: true
    },

    datalabels: {
      anchor: function(ctx) {
        switch (ctx.datasetIndex) {
          // daily economic growth
          case 0:
            return 'start';

          // total economic growth
          case 1:
            return 'start';
        };
      },

      align: function(ctx) {
        switch (ctx.datasetIndex) {
          // daily economic growth
          case 0:
            return 'start';

          // total economic growth
          case 1:
            return 'end';
        };
      },

      borderColor: function(ctx) {
        return ctx.dataset.backgroundColor;
      },

      borderWidth: 1,
      backgroundColor: function() {
        return 'rgb(19, 21, 22)';
      },

      borderRadius: 6,
      font: {
        weight: 'bold'
      },

      formatter: Math.round,
      color: function(ctx) {
        switch (ctx.datasetIndex) {
          // daily economic growth
          case 0:
            if (ctx.chart.data.datasets[ctx.datasetIndex].data[ctx.dataIndex] > 0) {
              // green(ish)
              return 'rgb(85, 224, 136)';
            }

            // red(ish)
            return 'rgb(248, 107, 107)';

          // total economic growth
          case 1:
            if (ctx.chart.data.datasets[ctx.datasetIndex].data[ctx.dataIndex] <= 0) {
              return 'rgb(248, 107, 107)';
            }
            return 'rgb(85, 224, 136)';
        };
      },
      display: (ctx) => {
        return false;
        //return ctx.datasetIndex === 1 && Math.abs(ctx.chart.data.datasets[ctx.datasetIndex].data[ctx.dataIndex]) > 1000;

      },
      padding: 6
    }
  },

  // Core options
  aspectRatio: 5 / 3,
  layout: {
    padding: {
      top: 32,
      right: 16,
      bottom: 16,
      left: 8
    }
  },

  elements: {
    line: {
      tension: 0.4
    }
  },
  scales: {
    y: {
      grid: {
        color: function(ctx) {
          if (ctx.tick.value === 0) {
            return 'red';
          }
        }
      }
    }
  }
}

export const expenseBreakdownOptions = {
  responsive: true,
  maintainAspectRatio: false,
  //borderColor: 'rgb(19, 21, 22)',
  borderColor: false,
  border: false,
  //onHover: (e, ctx) => {
  //  console.log({
  //      'resources/js/Components/Charts/chartConfig.js:131 onHover' : e,
  //  });
  //},
  plugins:{
    tooltip: {
      callbacks: {
        label: function(ctx) {
          let sum = 0;
          let dataArr = ctx.chart.data.datasets[0].data;
          let value = dataArr[ctx.dataIndex];
          dataArr.map(data => {
            sum += data;
          });
          let percentage = (value * 100 / sum).toFixed(2)+"%";
          let displayVal = new Intl.NumberFormat(
            'en-US',
            { style: 'currency', currency: 'USD' }
          ).format(value);
          let ret = [ `${displayVal} - ${percentage}` ];
          let transactions = ctx.chart.data.datasets[0].transactions[ctx.dataIndex];
          transactions.forEach((t) => {
            let catVal = new Intl.NumberFormat(
              'en-US',
              { style: 'currency', currency: 'USD' }
            ).format(t['cat_value']);
            let date = new Date(t['date'])
              .toLocaleString('us-en', {
                timeZone: "utc",
                weekday: "short",
                year: "numeric",
                month: "numeric",
                day: "numeric",
            });
            ret.push(`Transaction ${t['id']} on ${date} for ${catVal}`);
          });

          return ret
        }
      }
    },

    legend: {
      display: false
    },

    title: {
      text: 'Expense Breakdown',
      display: true
    },

    datalabels: {
      display: false
    /*
      anchor: 'end',
      align: 'start',
      listeners: {
        enter: (ctx, event) => {
          console.log({
              'resources/js/Components/Charts/chartConfig.js:143 enter' : event,
          });
          // Receives `enter` events for any labels of any dataset. Indices of the
          // clicked label are: `context.datasetIndex` and `context.dataIndex`.
          // For example, we can modify keep track of the hovered state and
          // return `true` to update the label and re-render the chart.
          ctx.hovered = true;
          return true;
        },

        leave: (ctx, event) => {
          // Receives `leave` events for any labels of any dataset.
          console.log({
            'resources/js/Components/Charts/chartConfig.js:143 leave' : event,
          });
          ctx.hovered = false;
          return true;
        }
      },

      display: (ctx) => {
        let sum = 0;
        let dataArr = ctx.chart.data.datasets[0].data;
        dataArr.map(data => {
          sum += data;
        });
        let value = dataArr[ctx.dataIndex];
        let percentage = value * 100 / sum
        return percentage > 4;
      },

      formatter: (value, ctx) => {
        let sum = 0;
        let dataArr = ctx.chart.data.datasets[0].data;
        dataArr.map(data => {
          sum += data;
        });
        let labelArr = ctx.chart.data.labels;
        let percentage = (value * 100 / sum).toFixed(2)+"%";
        let displayVal = value.toFixed(2);
        return ctx.active ?
          [ `${labelArr[ctx.dataIndex]}`, `$${displayVal}`,   `${percentage}` ]
          : null;
      },
      color: '#000000'
    */
    }
  },
  elements: {
    arc: {
      borderWidth: 0
    }
  }
}
