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

  accountGrowthLinesOptions.plugins.title.text = "Account Balance Changes";
  const defaultChartStruct = {
    datasets: [
      {
        label:"Balance over Time",
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
      try {
        lineChartData.value.labels.push(dateFormatter.format(new Date(date)));
      } catch (e) {
        lineChartData.value.labels.push(date);
      }
      lineChartData.value.datasets[0].data.push(dailyGrowth);
    }
  });

  const refreshChartData = () => {
    lineChartData.value = structuredClone(defaultChartStruct);
    for (let date in props.chartData) {
      let dailyGrowth = props.chartData[date];
      try {
        lineChartData.value.labels.push(dateFormatter.format(new Date(date)));
      } catch (e) {
        lineChartData.value.labels.push(date);
      }
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
