<script setup>
  import { ref, onMounted } from 'vue';
  import {
    Chart as ChartJS,
    ArcElement,
    Tooltip,
    Legend
  } from 'chart.js'
  import { Pie } from 'vue-chartjs'

  ChartJS.register(ArcElement, Tooltip, Legend);
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
  <div style="height: 1200px; width: 100%">
    <Pie
      v-if="mounted"
      :data="chartData"
      :options="chartOptions"
    />
  </div>
</template>
