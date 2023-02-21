<script setup>
  import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    BarElement,
    Title,
    Tooltip,
    Legend
  } from 'chart.js'
  import { Bar } from 'vue-chartjs'
  import * as chartConfig from './chartConfig.js'

  ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale)

  let props = defineProps({
    chartData: {
      type: Object,
      default: chartConfig.data
    },

    chartOptions: {
      type: Object,
      default: chartConfig.options
    }
  });
</script>

<template>
  <Bar
    id="my-chart-id"
    :options="chartOptions"
    :data="chartData"
  />
</template>
