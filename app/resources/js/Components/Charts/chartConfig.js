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

export const lineChartOptions = {
  responsive: true,
  tooltips: {
    enabled: false

  },
  maintainAspectRatio: false,
  plugins:{
    legend: {
      display: false
    },
    title: {
      text: 'Growths n shit',
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
        return [ `${labelArr[ctx.dataIndex]}`, `\$${displayVal}`,   `${percentage}` ];
      },
      color: '#000000'
    }
  }
}

export const pieChartOptions = {
  responsive: true,
  tooltips: {
    enabled: false

  },
  maintainAspectRatio: false,
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
        return [ `${labelArr[ctx.dataIndex]}`, `\$${displayVal}`,   `${percentage}` ];
      },
      color: '#000000'
    }
  }
}
