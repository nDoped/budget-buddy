<script setup>
  import { ref, onMounted } from 'vue';
  import {
    Chart as ChartJS,
    ArcElement,
    Tooltip,
    Legend
  } from 'chart.js'
  import { Pie } from 'vue-chartjs'
  import { pieData, pieChartOptions } from './chartConfig.js'
  import ChartDataLabels from 'chartjs-plugin-datalabels';

  ChartJS.register(ArcElement, Tooltip, Legend, ChartDataLabels);
  let props = defineProps({
    chartData: {
      type: Object,
      default: pieData
    },

    chartOptions: {
      type: Object,
      default: pieChartOptions
    }
  });

  const mounted = ref(false);
  onMounted(() => {
    mounted.value = true;
  });
</script>

<template>
  <Pie
    v-if="mounted"
    :data="chartData"
    :options="chartOptions"
  />
</template>
