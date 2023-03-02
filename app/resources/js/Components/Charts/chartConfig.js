export const data = {
  labels: ['January', 'February', 'March', 'April', 'May', 'June', 'July'],
  datasets: [
    {
      label: 'Data One',
      backgroundColor: '#f87979',
      data: [40, 39, 10, 40, 39, 80, 40]
    },
    {
      label: 'Data Two',
      backgroundColor: '#fffff',
      data: [22, 10, 90, 88, 43, 66, 25]
    }
  ]
}

export const pieData = {
  labels: ['VueJs', 'EmberJs', 'ReactJs', 'AngularJs'],
  datasets: [
    {
      backgroundColor: ['#41B883', '#E46651', '#00D8FF', '#DD1B16'],
      data: [40, 20, 80, 10]
    }
  ]
}

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
      text: 'Net Growth Values',
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
  tooltips: {
    enabled: false
  },
  maintainAspectRatio: false,
  //borderColor: 'rgb(19, 21, 22)',
  borderColor: false,
  border: false,
  plugins:{
    legend: {
      display: false
    },
    title: {
      text: 'Breakdown of expenses',
      display: true
    },
    datalabels: {
      anchor: 'end',
      align: 'start',
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
        return [ `${labelArr[ctx.dataIndex]}`, `$${displayVal}`,   `${percentage}` ];
      },
      color: '#000000'
    }
  },
  elements: {
    arc: {
      borderWidth: 0
    }
  }
}
