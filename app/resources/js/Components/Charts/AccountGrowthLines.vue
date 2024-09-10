<script setup>
  import {  inject, watch, ref, onMounted } from 'vue';
  import LineChart from '@/Components/Charts/LineChart.vue';
  import { accountGrowthLinesOptions } from './chartConfig.js'
  const dateFormatter = inject('dateFormatter');

  let props = defineProps({
    chartData: {
      type: Object,
      default: () => {}
    }
  });

  const defaultChartStruct = {
    datasets: [
      {
        label:"Daily Economic Growth",
        backgroundColor: 'rgb(75, 192, 192)',
        data: [ ],
        borderColor: 'rgb(75, 192, 192)',
        //showLine: false
      },
      {
        label:"Total Economic Growth",
        backgroundColor: 'rgb(75, 53, 134)',
        borderColor: 'rgb(75, 53, 134)',
        data: [ ],
        //showLine: false
      }
    ],
    labels: [ ]
  };

  const lineChartData = ref(structuredClone(defaultChartStruct));
  onMounted(() => {

    for (let date in props.chartData.daily_economic_growth) {
      let dailyGrowth = props.chartData.daily_economic_growth[date];
      lineChartData.value.labels.push(dateFormatter.format(new Date(date)));
      lineChartData.value.datasets[0].data.push(dailyGrowth);
    }

    for (let date in props.chartData.total_economic_growth) {
      let dailyGrowth = props.chartData.total_economic_growth[date];
      lineChartData.value.datasets[1].data.push(dailyGrowth);
    }
  });

  const refreshChartData = () => {
    lineChartData.value = structuredClone(defaultChartStruct);
    for (let date in props.chartData.daily_economic_growth) {
      let dailyGrowth = props.chartData.daily_economic_growth[date];
      lineChartData.value.labels.push(dateFormatter.format(new Date(date)));
      lineChartData.value.datasets[0].data.push(dailyGrowth);
    }
    for (let date in props.chartData.total_economic_growth) {
      let dailyGrowth = props.chartData.total_economic_growth[date];
      lineChartData.value.datasets[1].data.push(dailyGrowth);
    }
  };
  watch(() => props.chartData.daily_economic_growth, refreshChartData);
  watch(() => props.chartData.total_economic_growth, refreshChartData);
</script>

<template>
  <LineChart
    :chart-data="lineChartData"
    :chart-options="accountGrowthLinesOptions"
  />
</template>
