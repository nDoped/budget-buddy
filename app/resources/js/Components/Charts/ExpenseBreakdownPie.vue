<script setup>
  import { watch, ref, onMounted } from 'vue';
  import PieChart from '@/Components/Charts/PieChart.vue';
  import { expenseBreakdownOptions } from './chartConfig.js'

  let props = defineProps({
    categorizedExpenses: {
      type: Object,
      default: () => {}
    }
  });

  const defaultChartStruct = {
    labels: [],
    datasets: [
      {
        backgroundColor: [],
        data: [],
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
      pieChartData.value.labels.push(props.categorizedExpenses[id].name);
    }
  });

  onMounted(() => {
    for (let id in sortObj(props.categorizedExpenses)) {
      pieChartData.value.datasets[0].data.push(props.categorizedExpenses[id].value);
      pieChartData.value.datasets[0].backgroundColor.push(props.categorizedExpenses[id].color);
      pieChartData.value.labels.push(props.categorizedExpenses[id].name);
    }
  });
</script>

<template>
  <PieChart
    :chart-data="pieChartData"
    :chart-options="expenseBreakdownOptions"
  />
</template>
<style>
.chart-wrapper {
  display: inline-block;
  position: relative;
  width: 100%;
}
</style>
