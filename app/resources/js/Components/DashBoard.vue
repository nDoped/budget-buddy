<script setup>
  import { useForm } from '@inertiajs/vue3'
  import { inject, ref } from 'vue';
  import AccountUrlLink from '@/Components/AccountUrlLink.vue';
  import DateFilter from '@/Components/DateFilter.vue';
  import GrowthLines from '@/Components/Charts/AccountGrowthLines.vue';
  import ExpenseBreakdown from '@/Components/Charts/ExpenseBreakdownPie.vue';
  import ExpandableTable from '@/Components/ExpandableTable.vue';
  const formatter = inject('formatter');

  defineProps({
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
        <template #range_button_text>
          Crunch the Numbers
        </template>
      </DateFilter>
    </div>
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
          :expand="false"
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
                <AccountUrlLink :url="item['url']" />
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
          :expand="false"
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
                <AccountUrlLink :url="item['url']" />
              </template>
            </div>
          </template>
        </ExpandableTable>
      </div>
    </div>
  </div>

  <div class="p-6 bg-zinc-300 dark:text-white dark:bg-zinc-900">
    <h1> Total Economic Growth: {{ formatter.format(totalEconomicGrowth) }} </h1>
  </div>

  <!--
  <div class="chart-wrapper bg-slate-300 dark:bg-gray-800 bg-opacity-75 h-[32rem]">
    <div class="h-full bg-slate-700 bg-opacity-75 grid grid-cols-2 md:grid-cols-2">
      <div class="m-5">
        <ExpenseBreakdown :categorized-expenses="categorizedExpenses" />
      </div>
      <div class="m-5">
        <GrowthLines :chart-data="accountGrowthLineData" />
      </div>
    </div>
  </div>
  -->
  <div class="bg-slate-300 dark:bg-gray-800 bg-opacity-75 h-[32rem]">
    <div class="w-full h-full bg-slate-700 bg-opacity-75 grid grid-cols-3">
      <div class="m-5">
        <ExpenseBreakdown :categorized-expenses="categorizedExpenses" />
      </div>
      <div class="m-5 col-span-2">
        <GrowthLines :chart-data="accountGrowthLineData" />
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
