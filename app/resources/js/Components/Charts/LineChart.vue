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
  import { data, lineChartOptions } from './chartConfig.js'

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
      default: data
    },

    chartOptions: {
      type: Object,
      default: lineChartOptions
    }
  });
</script>

<template>
  <Line
    :data="chartData"
    :options="chartOptions"
  />
</template>
