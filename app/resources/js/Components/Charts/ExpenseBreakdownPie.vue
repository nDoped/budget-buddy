<script setup>
  import {
    watch,
    ref,
    onMounted
  } from 'vue';
  import PieChart from '@/Components/Charts/PieChart.vue';
  import { expenseBreakdownOptions } from './chartConfig.js'
  import cloneDeep from 'lodash/cloneDeep';

  let props = defineProps({
    categorizedExpenses: {
      type: Object,
      default: () => {}
    },
    title: {
      type: String,
      default: () => "Here's some data"
    },
    color: {
      type: String,
      default: () => "#ffffff"
    }
  });

  const defaultChartStruct = {
    labels: [],
    datasets: [
      {
        backgroundColor: [],
        data: [],
        transactions: []
      }
    ],
  };

  const sortObj = (obj) => {
    return Object.keys(obj).sort().reduce(function (result, key) {
      result[key] = obj[key];
      return result;
    }, {});
  };

  const pieChartData = ref(structuredClone(defaultChartStruct));
  watch(() => props.categorizedExpenses, () => {
    pieChartData.value = structuredClone(defaultChartStruct);
    for (let id in sortObj(props.categorizedExpenses)) {
      pieChartData.value.datasets[0].data.push(props.categorizedExpenses[id].value);
      pieChartData.value.datasets[0].backgroundColor.push(props.categorizedExpenses[id].color);
      pieChartData.value.datasets[0].transactions.push(props.categorizedExpenses[id].transactions);
      pieChartData.value.labels.push(props.categorizedExpenses[id].name);
    }
  });

  const options = cloneDeep(expenseBreakdownOptions);
  onMounted(() => {
    for (let id in sortObj(props.categorizedExpenses)) {
      pieChartData.value.datasets[0].data.push(props.categorizedExpenses[id].value);
      pieChartData.value.datasets[0].backgroundColor.push(props.categorizedExpenses[id].color);
      pieChartData.value.datasets[0].transactions.push(props.categorizedExpenses[id].transactions);
      pieChartData.value.labels.push(props.categorizedExpenses[id].name);
    }
    options.plugins.title.text = props.title;
    options.plugins.title.font = {
      weight: 'bold',
      size:24
    };
    options.plugins.title.color = props.color;
  });
</script>

<template>
  <PieChart
    :chart-data="pieChartData"
    :chart-options="options"
  />
</template>
<style>
.chart-wrapper {
  display: inline-block;
  position: relative;
  width: 100%;
}
</style>
