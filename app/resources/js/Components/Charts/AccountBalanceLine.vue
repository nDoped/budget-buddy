<script setup>
  import {  watch, ref, onMounted } from 'vue';
  import LineChart from '@/Components/Charts/LineChart.vue';
  import { accountGrowthLinesOptions } from './chartConfig.js'

  let props = defineProps({
    chartData: {
      type: Object,
      default: () => {}
    }
  });

  const defaultChartStruct = {
    datasets: [
      {
        label:"Daily Balance",
        backgroundColor: 'rgb(75, 192, 192)',
        data: [ ],
        borderColor: 'rgb(75, 192, 192)',
        //showLine: false
      },
    ],
    labels: [ ]
  };

  const lineChartData = ref(structuredClone(defaultChartStruct));
  onMounted(() => {
    for (let date in props.chartData) {
      let dailyGrowth = props.chartData[date];
      lineChartData.value.labels.push(date);
      lineChartData.value.datasets[0].data.push(dailyGrowth);
    }
  });

  const refreshChartData = () => {
    lineChartData.value = structuredClone(defaultChartStruct);
    for (let date in props.chartData) {
      let dailyGrowth = props.chartData[date];
      lineChartData.value.labels.push(date);
      lineChartData.value.datasets[0].data.push(dailyGrowth);
    }
  };
  watch(() => props.chartData, refreshChartData);
</script>

<template>
  <LineChart
    :chart-data="lineChartData"
    :chart-options="accountGrowthLinesOptions"
  />
</template>
