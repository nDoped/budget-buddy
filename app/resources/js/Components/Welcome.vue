<script setup>
import { useForm } from '@inertiajs/vue3'
import { ref } from 'vue';
import InputDate from '@/Components/InputDate.vue';
import InputLabel from '@/Components/InputLabel.vue';
import DateFilter from '@/Components/DateFilter.vue';
import GrowthTable from '@/Components/DataTable.vue';
import BarChart from '@/Components/Charts/BarChart.vue';
import LineChart from '@/Components/Charts/LineChart.vue';

let props = defineProps({
  assets: Object,
  debts: Object,
  start: String,
  end: String
});
const account_fields = ref([
  { key: 'name', label: 'Name', highlight:false, has_url:true },
  { key: 'start_balance', label: 'Start Balance', highlight: true },
  { key: 'in_range_net_growth', label: 'Net Growth', highlight: true },
  { key: 'end_balance', label: 'End Balance', highlight: true }
]);
const filterTransactionsForm = useForm({});
const crunchIt = (data) => {
  console.log(data.value);
    filterTransactionsForm.get(route('dashboard', data.value), {
        preserveScroll: true,
        preserveState: true,
    });
}

</script>

<template>
  <div class="p-6 sm:px-20 bg-slate-700 border-b border-gray-200">
    <div class="grid grid-cols-2 gap-4">
      <div class="overflow-x-auto sm:-mx-6 lg:-mx-8">
        <div class="py-2 min-w-full sm:px-6 lg:px-8">
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

      <div style="text-align: left">
        <h2>Assets</h2>
        <GrowthTable
          :items="assets"
          :asset="true"
          :fields="account_fields"
        />

        <h2>Debts</h2>
        <GrowthTable
          :items="debts"
          :asset="false"
          :fields="account_fields"
        />
      </div>
    </div>

  </div>

  <div class="bg-slate-900 bg-opacity-25 grid grid-cols-1 md:grid-cols-2">
    <div class="p-6">
      <div class="flex items-center">
        Got data?
      </div>

      <div class="ml-12">
        <div class="ml-1 text-indigo-500">
          <BarChart />
        </div>
      </div>
    </div>

    <div class="p-6 border-t border-gray-200 md:border-t-0 md:border-l">
      <div class="flex items-center">
        Some more data
      </div>

      <div class="ml-12">
        <div class="mt-3 flex items-center text-sm font-semibold text-indigo-700">
          <LineChart />
        </div>
      </div>
    </div>

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
  </div>
</template>
