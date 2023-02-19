<script setup>
  import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend
  } from 'chart.js'
  import { Line } from 'vue-chartjs'
  import * as chartConfig from './chartConfig.js'

  ChartJS.register(
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend
  )
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
  <Line
    :data="chartData"
    :options="chartOptions"
  />
</template>

