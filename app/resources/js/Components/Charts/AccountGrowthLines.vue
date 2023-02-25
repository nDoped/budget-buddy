<script setup>
  import { watch, ref, onMounted } from 'vue';
  import LineChart from '@/Components/Charts/LineChart.vue';

  let props = defineProps({
    accountGrowthLineData: Object,
  });

  const filterTransactionsForm = useForm({});
  const crunchIt = (data) => {
    filterTransactionsForm.get(route('dashboard', data.value), {
      preserveScroll: true,
      preserveState: true,
    });
  }
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


  const lineChartData = ref(structuredClone(defaultChartStruct));
  watch(() => props.accountGrowthLineData, (newData) => {
    lineChartData.value = structuredClone(defaultChartStruct);
    for (let id in props.accountGrowthLineData) {
      console.log(id);
      console.log(props.accountGrowthLineData[id]);
      /*
      lineChartData.value.datasets[0].data.push(props.categorizedExpenses[id].value);
      lineChartData.value.datasets[0].backgroundColor.push(props.categorizedExpenses[id].color);
      lineChartData.value.labels.push(props.categorizedExpenses[id].name);
       */
    }
  });

  onMounted(() => {
    for (let id in sortObj(props.accountGrowthLineData)) {
      /*
      lineChartData.value.datasets[0].data.push(props.categorizedExpenses[id].value);
      lineChartData.value.datasets[0].backgroundColor.push(props.categorizedExpenses[id].color);
      lineChartData.value.labels.push(props.categorizedExpenses[id].name);
       */
    }
  });
</script>

<template>
  <LineChart :chartData="lineChartData"/>
</template>
