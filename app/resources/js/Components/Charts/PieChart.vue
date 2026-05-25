<script setup>
  import { ref, onMounted } from 'vue';
  import {
    Chart as ChartJS,
    ArcElement,
    Tooltip,
    Legend
  } from 'chart.js'
  import { Pie } from 'vue-chartjs'
  import ChartDataLabels from 'chartjs-plugin-datalabels';

  ChartJS.register(ArcElement, Tooltip, Legend, ChartDataLabels);
  defineProps({
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
  <div class="h-full w-full">
    <Pie
      v-if="mounted"
      :data="chartData"
      :options="chartOptions"
    />
  </div>
</template>
