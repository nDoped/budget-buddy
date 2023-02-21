<script setup>
  import { useForm } from '@inertiajs/vue3'
  import { watch, ref, onMounted, shallowRef } from 'vue';
  import InputDate from '@/Components/InputDate.vue';
  import InputLabel from '@/Components/InputLabel.vue';
  import DateFilter from '@/Components/DateFilter.vue';
  import GrowthTable from '@/Components/DataTable.vue';
  import BarChart from '@/Components/Charts/BarChart.vue';
  import LineChart from '@/Components/Charts/LineChart.vue';
  import PieChart from '@/Components/Charts/PieChart.vue';

  let props = defineProps({
    assets: Object,
    debts: Object,
    categorizedExpenses: Object,
    totalEconomicGrowth: Number,
    start: String,
    end: String
  });

  const tableConfig = ref({
    'has_totals_row': true,
  });
  const tableFields = ref([
    { key: 'name', label: 'Name', highlight:false, has_url:true },
    { key: 'start_balance', label: 'Start Balance', highlight: true },
    { key: 'in_range_net_growth', label: 'Net Growth', highlight: true },
    { key: 'end_balance', label: 'End Balance', highlight: true }
  ]);
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
  const chartData = ref(structuredClone(defaultChartStruct));
  watch(() => props.categorizedExpenses, (newCats) => {
    chartData.value = structuredClone(defaultChartStruct);
    for (let id in props.categorizedExpenses) {
      chartData.value.datasets[0].data.push(props.categorizedExpenses[id].value);
      chartData.value.datasets[0].backgroundColor.push(props.categorizedExpenses[id].color);
      chartData.value.labels.push(props.categorizedExpenses[id].name);
    }
  });
  onMounted(() => {
    for (let id in props.categorizedExpenses) {
      chartData.value.datasets[0].data.push(props.categorizedExpenses[id].value);
      chartData.value.datasets[0].backgroundColor.push(props.categorizedExpenses[id].color);
      chartData.value.labels.push(props.categorizedExpenses[id].name);
    }
  });
</script>

<template>
  <div class="p-6 sm:px-20 bg-slate-700 border-b border-gray-200">
    <div class="grid grid-cols-2 gap-4">
      <div class="overflow-hidden" style="text-align: left">
        <DateFilter
          :start="start"
          :end="end"
          @filter="crunchIt"
          :processing="filterTransactionsForm.processing"
        >
          Crunch the Numbers
        </DateFilter>
      </div>
    </div>
  </div>

  <div class="chart-wrapper bg-slate-700 bg-opacity-75 h-[32rem]">
    <div class="h-full bg-slate-700 bg-opacity-75 grid grid-cols-2 md:grid-cols-2">
      <div class="chart-wrapper">
        <PieChart :chartData="chartData" />
      </div>
      <div class="chart-wrapper">
        <BarChart />
      </div>
    </div>
  </div>

  <div class="p-6">
    <h1> Total Economic Growth: {{ totalEconomicGrowth }} </h1>
  </div>
  <div class="bg-slate-700 bg-opacity-75 grid grid-cols-1 md:grid-cols-2">
    <div class="p-6">
      <div class="flex items-center flex-col">
        <div class="text-3xl text-bold">
          Debts
        </div>

        <div>
          <GrowthTable
            :items="debts"
            :asset="false"
            :hasTotalsRow="true"
            :tableConfig="tableConfig"
            :fields="tableFields"
          />
        </div>
      </div>
    </div>

    <div class="p-6 border-t border-gray-200 md:border-t-0 md:border-l">
      <div class="flex flex-col items-center">
        <div class="text-3xl text-bold">
          Assets
        </div>
        <div>
          <GrowthTable
            :items="assets"
            :asset="true"
            :tableConfig="tableConfig"
            :fields="tableFields"
          />
        </div>
      </div>
    </div>

    <!--
    <div class="p-6 border-t border-gray-200">
      <div class="flex items-center">

        <div class="ml-4 text-lg text-gray-600 leading-7 font-semibold">
          ??
        </div>
        <div class="ml-4 text-lg text-gray-600 leading-7 font-semibold">
          itsastring
        </div>
      </div>

      <div class="ml-12">
        <div class="mt-2 text-sm text-gray-500">
          sure it is
        </div>
      </div>
    </div>

    <div class="p-6 border-t border-gray-200 md:border-l">
      <div class="flex items-center">
        <div class="ml-4 text-lg text-gray-600 leading-7 font-semibold">
          Here
        </div>
      </div>

      <div class="ml-12">
        <div class="mt-2 text-sm text-gray-500">
          there
        </div>
      </div>
    </div>
    -->
  </div>
</template>
<style>
.chart-wrapper {
  display: inline-block;
  position: relative;
  width: 100%;
}
</style>
