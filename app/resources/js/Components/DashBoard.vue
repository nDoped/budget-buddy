<script setup>
  import { useForm } from '@inertiajs/vue3';
  import {
    inject,
    computed,
    onMounted,
    ref
  } from 'vue';
  import AccountUrlLink from '@/Components/AccountUrlLink.vue';
  import DateFilter from '@/Components/DateFilter.vue';
  import GrowthLines from '@/Components/Charts/AccountGrowthLines.vue';
  import AccountBalanceLine from '@/Components/Charts/AccountBalanceLine.vue';
  import ExpenseBreakdown from '@/Components/Charts/ExpenseBreakdownPie.vue';
  import ExpandableTable from '@/Components/ExpandableTable.vue';
  import StatsComponent from '@/Components/StatsComponent.vue';
  const formatter = inject('formatter');

  const props = defineProps({
    assets: {
      type: Object,
      default: () => {}
    },
    debts: {
      type: Object,
      default: () => {}
    },
    categoryTypeBreakdowns: {
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

  const rangeDisplay = computed(() => {
    let ret = '';
    let start = (props.start) ?
      new Date(props.start)
        .toLocaleString('us-en', {
          timeZone: "utc",
          weekday: "short",
          year: "numeric",
          month: "numeric",
          day: "numeric",
        })
      : null;

    let end = (props.end) ?
      new Date(props.end)
        .toLocaleString('us-en', {
          timeZone: "utc",
          weekday: "short",
          year: "numeric",
          month: "numeric",
          day: "numeric",
        })
      : null;

    if (start && end) {
      ret = `Showing data from ${start} to ${end}`;
    } else if (start) {
      ret = `Showing data from ${start} until the end of time`;
    } else if (end) {
      ret = `Showing data from the beginning of time until ${end}`;
    } else {
      ret = 'Showing everything';
    }
    return ret;
  });

  const dashboardStatsTotals = computed(() => {
    let ret = [];
    // kinda hacky and depends on the asset and debt accounts having a totals
    // row...which i might refactor, but it's late and this will work
    let assetGrowth = props.assets[props.assets.length - 1].in_range_net_growth;
    let statClass = 'text-green-400';
    if (assetGrowth <= 0) {
      statClass =  'text-red-400';
    }
    ret.push({
      title: 'Asset Growth',
      value: formatter.format(assetGrowth),
      class: statClass
    });

    let debtGrowth = props.debts[props.debts.length - 1].in_range_net_growth;
    statClass = 'text-green-400';
    if (debtGrowth > 0) {
      statClass =  'text-red-400';
    }
    ret.push({
      title: 'Debt Growth',
      value: formatter.format(debtGrowth),
      class: statClass
    });

    if (props.totalEconomicGrowth) {
      let statClass = 'text-green-400';
      if (props.totalEconomicGrowth <= 0) {
        statClass =  'text-red-400';
      }
      ret.push({
        title: 'Economic Growth',
        value: formatter.format(props.totalEconomicGrowth),
        class: statClass
      });
    }
    return ret;
  });

  const dashboardStats = computed(() => {
    let ret = [];

    for (let i in props.categoryTypeBreakdowns) {
      ret.push({
        title: props.categoryTypeBreakdowns[i].name,
        value: formatter.format(props.categoryTypeBreakdowns[i].total),
        color: props.categoryTypeBreakdowns[i].color
      });
    }
    return ret;
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

  const mounted = ref(false);
  onMounted(() => {
    mounted.value = true;
  });

  const getBreakdownTitle = (br) => {
    return br.name + ': ' + formatter.format(br.total);
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
  <div class="p-6 sm:px-20 dark:bg-slate-700 border-b border-gray-200">
    <div
      class="max-w-xl"
      style="text-align: left"
    >
      <DateFilter
        :start="start"
        :end="end"
        :show-transactions-link="true"
        @filter="crunchIt"
        :processing="filterTransactionsForm.processing"
      >
        <template #range_button_text>
          Crunch the Numbers
        </template>
      </DateFilter>
    </div>
  </div>

  <div class="p-6 bg-zinc-300 dark:text-white dark:bg-slate-700">
    <StatsComponent
      :stats="dashboardStatsTotals"
      :last="rangeDisplay"
    />
  </div>

  <div class="w-full dark:bg-slate-700 bg-opacity-75 grid grid-cols-1 xl:grid-cols-2">
    <div class="p-6">
      <div class="flex items-center flex-col overflow-x-auto">
        <div class="text-3xl text-bold">
          Debts
        </div>

        <ExpandableTable
          class="grow w-full"
          :pagination-start="100"
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
                <AccountUrlLink :url="item['url']" />
              </template>
            </div>
          </template>

          <template #hidden_row="{item}">
            <AccountBalanceLine :chart-data="item['daily_balance_line_graph_data']" />
          </template>
        </ExpandableTable>
      </div>
    </div>

    <div class="p-6 border-t border-gray-200 md:border-t-0 md:border-l">
      <div class="flex flex-col items-center overflow-x-auto">
        <div class="text-3xl text-bold">
          Assets
        </div>
        <ExpandableTable
          class="grow w-full"
          :items="assets"
          :pagination-start="100"
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
                <AccountUrlLink :url="item['url']" />
              </template>
            </div>
          </template>

          <template #hidden_row="{ item }">
            <AccountBalanceLine :chart-data="item['daily_balance_line_graph_data']" />
          </template>
        </ExpandableTable>
      </div>
    </div>
  </div>

  <div class="bg-slate-300 dark:bg-gray-800 bg-opacity-75 h-[32rem]">
    <div class="w-full h-full bg-slate-700 bg-opacity-75 grid grid-cols-1">
      <div class="m-2 col-span-2">
        <GrowthLines :chart-data="accountGrowthLineData" />
      </div>
    </div>
  </div>

  <div class="p-6 bg-zinc-300 dark:text-white dark:bg-slate-700">
    <StatsComponent
      :stats="dashboardStats"
      last=""
    />
  </div>

  <div class="bg-slate-300 dark:bg-gray-800 bg-opacity-75">
    <div class="h-full bg-slate-700 bg-opacity-75 grid gap-6 grid-cols-1 md:grid-cols-2 lg:grid-cols-3">
      <div
        v-for="(br, i) in categoryTypeBreakdowns"
        class="m-2 h-[36rem] text-center"
        :key="i"
      >
        <ExpenseBreakdown
          v-if="mounted && ! filterTransactionsForm.processing"
          :categorized-expenses="br.data"
          :title="getBreakdownTitle(br)"
          :color="br.color"
        />
      </div>
    </div>
  </div>
</template>
