<script setup>
  import { ref, onMounted } from 'vue'
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

  ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale)

  defineProps({
    chartData: {
      type: Object,
      default: () => ({ labels: [], datasets: [] })
    },
    chartOptions: {
      type: Object,
      default: () => ({})
    }
  })

  const mounted = ref(false)
  onMounted(() => {
    mounted.value = true
  })
</script>

<template>
  <div class="h-full w-full">
    <Bar
      v-if="mounted"
      :options="chartOptions"
      :data="chartData"
    />
  </div>
</template>
