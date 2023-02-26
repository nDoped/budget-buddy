<script setup>
  import { useForm } from '@inertiajs/vue3'
  import { inject, watch, ref, onMounted } from 'vue';
  import DateFilter from '@/Components/DateFilter.vue';
  import LineChart from '@/Components/Charts/LineChart.vue';
  import ExpenseBreakdown from '@/Components/Charts/ExpenseBreakdownPie.vue';
  import ExpandableTable from '@/Components/ExpandableTable.vue';
  const formatter = inject('formatter');

  let props = defineProps({
    assets: {
      type: Object,
      default: () => {}
    },
    debts: {
      type: Object,
      default: () => {}
    },
    categorizedExpenses: {
      type: Object,
      default: () => {}
    },
    accountGrowthLineData: {
      type: Object,
      default: () => {}
    },
    totalEconomicGrowth: {
      type: Number,
      default: () => 0
    },
    start: {
      type: String,
      default: () => ''
    },
    end: {
      type: String,
      default: () => ''
    }
  });

  const fields = ref([
    { key: 'name', label: 'Name', sortable: true, highlight:false, has_url:true, format: false },
    { key: 'start_balance', label: 'Start Balance', highlight: true, has_url: false, format:true },
    { key: 'in_range_net_growth', sortable: true, label: 'Net Growth', highlight: true, has_url: false, format:true },
    { key: 'end_balance', label: 'End Balance', highlight: true, has_url: false, format:true }
  ]);

  const hasUrl = (key) => {
    let test = fields.value.find(field => field.key === key );
    if (test.has_url) {
      return true;
    }
    return false;
  };

  const formatField = (key) => {
    let test = fields.value.find(field => field.key === key );
    if (test.format) {
      return true;
    }
    return false;
  };

  const colorText = (key) => {
    let test = fields.value.find(field => field.key === key );
    if (test.highlight) {
      return true;
    }
    return false;
  };
  const textColor = (item, value, highlight) => {
    let ret = 'text-slate-400';
    if (! highlight) {
      return ret;
    }
    if (item.asset) {
      if (value > 0) {
        ret = 'text-green-400';
      } else {
        ret = 'text-red-400';
      }

    } else {
      if (value > 0) {
        ret = 'text-red-400';
      } else {
        ret = 'text-green-400';
      }
    }
    return ret;
  };

  const filterTransactionsForm = useForm({});
  const crunchIt = (data) => {
    /* global route */
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
  watch(() => props.accountGrowthLineData, () => {
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
      console.log(id);
      console.log(props.accountGrowthLineData[id]);
      /*
      lineChartData.value.datasets[0].data.push(props.categorizedExpenses[id].value);
      lineChartData.value.datasets[0].backgroundColor.push(props.categorizedExpenses[id].color);
      lineChartData.value.labels.push(props.categorizedExpenses[id].name);
       */
    }
  });
</script>

<template>
  <div class="p-6 sm:px-20 bg-slate-700 border-b border-gray-200">
    <div
      class="max-w-xl overflow-hidden"
      style="text-align: left"
    >
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

  <div class="chart-wrapper bg-slate-300 dark:bg-gray-800 bg-opacity-75 h-[32rem]">
    <div class="h-full bg-slate-700 bg-opacity-75 grid grid-cols-2 md:grid-cols-2">
      <div class="m-5">
        <ExpenseBreakdown :categorized-expenses="categorizedExpenses" />
      </div>
      <div class="m-5">
        <LineChart :chart-data="lineChartData" />
      </div>
    </div>
  </div>

  <div class="p-6 bg-zinc-300 dark:text-white dark:bg-zinc-900">
    <h1> Total Economic Growth: {{ formatter.format(totalEconomicGrowth) }} </h1>
  </div>
  <div class="w-full bg-slate-700 bg-opacity-75 grid grid-cols-1 md:grid-cols-2">
    <div class="p-6">
      <div class="flex items-center flex-col">
        <div class="text-3xl text-bold">
          Debts
        </div>

        <ExpandableTable
          class="grow w-full bg-gray-800 text-black"
          :items="debts"
          :fields="fields"
        >
          <template #visible_row="{ item , value, key }">
            <div
              :class="textColor(item, value, colorText(key,value,item))"
              class="font-semibold text-l"
            >
              <template v-if="formatField(key, value, item)">
                {{ formatter.format(value) }}
              </template>
              <template v-else>
                {{ value }}
              </template>

              <template v-if="hasUrl(key, value, item) && item['url']">
                <a
                  :href="item['url']"
                  target="_blank"
                  class="ml-1"
                >
                  <svg
                    fill="#000000"
                    style="display:inline"
                    version="1.1"
                    id="Capa_1"
                    xmlns="http://www.w3.org/2000/svg"
                    xmlns:xlink="http://www.w3.org/1999/xlink"
                    width="15px"
                    height="15px"
                    viewBox="0 0 393.789 393.789"
                    xml:space="preserve"
                  >
                    <path
                      d="M304.9,190.873c-5.449,0-9.865,4.422-9.865,9.864v141.033c0,17.805-14.482,32.283-32.285,32.283H52.015
                      c-17.802,0-32.284-14.479-32.284-32.283V131.037c0-17.795,14.482-32.285,32.284-32.285h141.033c5.448,0,9.866-4.412,9.866-9.865
                      c0-5.443-4.418-9.865-9.866-9.865H52.015C23.334,79.022,0,102.356,0,131.038v210.734c0,28.682,23.334,52.014,52.015,52.014H262.75
                      c28.682,0,52.016-23.332,52.016-52.014V200.737C314.766,195.295,310.348,190.873,304.9,190.873z"
                    />

                    <path
                      d="M304.9,0.003c-49.016,0-88.895,39.876-88.895,88.884c0,49.02,39.879,88.895,88.895,88.895
                      c49.012,0,88.889-39.875,88.889-88.895C393.789,39.879,353.912,0.003,304.9,0.003z M304.9,158.051
                      c-38.137,0-69.164-31.021-69.164-69.164c0-38.131,31.027-69.153,69.164-69.153c38.133,0,69.158,31.022,69.158,69.153
                      C374.059,127.029,343.033,158.051,304.9,158.051z"
                    />
                  </svg>
                </a>
              </template>
            </div>
          </template>
        </ExpandableTable>
      </div>
    </div>

    <div class="p-6 border-t border-gray-200 md:border-t-0 md:border-l">
      <div class="flex flex-col items-center">
        <div class="text-3xl text-bold">
          Assets
        </div>
        <ExpandableTable
          class="grow w-full bg-gray-800 text-black"
          :items="assets"
          :fields="fields"
        >
          <template #visible_row="{ item , value, key }">
            <div
              :class="textColor(item, value, colorText(key,value,item))"
              class="font-semibold text-l"
            >
              <template v-if="formatField(key, value, item)">
                {{ formatter.format(value) }}
              </template>
              <template v-else>
                {{ value }}
              </template>

              <template v-if="hasUrl(key, value, item) && item['url']">
                <a
                  :href="item['url']"
                  target="_blank"
                  class="ml-1"
                >
                  <svg
                    fill="#000000"
                    style="display:inline"
                    version="1.1"
                    id="Capa_1"
                    xmlns="http://www.w3.org/2000/svg"
                    xmlns:xlink="http://www.w3.org/1999/xlink"
                    width="15px"
                    height="15px"
                    viewBox="0 0 393.789 393.789"
                    xml:space="preserve"
                  >
                    <path
                      d="M304.9,190.873c-5.449,0-9.865,4.422-9.865,9.864v141.033c0,17.805-14.482,32.283-32.285,32.283H52.015
                      c-17.802,0-32.284-14.479-32.284-32.283V131.037c0-17.795,14.482-32.285,32.284-32.285h141.033c5.448,0,9.866-4.412,9.866-9.865
                      c0-5.443-4.418-9.865-9.866-9.865H52.015C23.334,79.022,0,102.356,0,131.038v210.734c0,28.682,23.334,52.014,52.015,52.014H262.75
                      c28.682,0,52.016-23.332,52.016-52.014V200.737C314.766,195.295,310.348,190.873,304.9,190.873z"
                    />

                    <path
                      d="M304.9,0.003c-49.016,0-88.895,39.876-88.895,88.884c0,49.02,39.879,88.895,88.895,88.895
                      c49.012,0,88.889-39.875,88.889-88.895C393.789,39.879,353.912,0.003,304.9,0.003z M304.9,158.051
                      c-38.137,0-69.164-31.021-69.164-69.164c0-38.131,31.027-69.153,69.164-69.153c38.133,0,69.158,31.022,69.158,69.153
                      C374.059,127.029,343.033,158.051,304.9,158.051z"
                    />
                  </svg>
                </a>
              </template>
            </div>
          </template>
        </ExpandableTable>
      </div>
    </div>
  </div>
</template>
<style>
.chart-wrapper {
  display: inline-block;
  position: relative;
  width: 100%;
}
</style>
