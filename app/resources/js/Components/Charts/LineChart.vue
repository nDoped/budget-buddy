<script setup>
  import { ref, onMounted } from 'vue';
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
      default: () => {}
    },

    chartOptions: {
      type: Object,
      default: () => {}
    }
  });
  const mounted = ref(false);
  onMounted(() => {
    mounted.value = true;
  });
</script>

<template>
  <Line
    v-if="mounted"
    :data="chartData"
    :options="chartOptions"
  />
</template>
