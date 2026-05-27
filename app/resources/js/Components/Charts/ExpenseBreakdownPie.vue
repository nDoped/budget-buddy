<script setup>
  import {
    computed,
    ref,
    reactive,
    onMounted
  } from 'vue';
  import { router } from '@inertiajs/vue3';
  import { Chart as ChartJS, ArcElement, Tooltip, Legend } from 'chart.js';
  import { Pie } from 'vue-chartjs';
  import { expenseBreakdownOptions } from './chartConfig.js'
  import cloneDeep from 'lodash/cloneDeep';

  ChartJS.register(ArcElement, Tooltip, Legend);

  const props = defineProps({
    categorizedExpenses: {
      type: Object,
      default: () => ({})
    },
    title: {
      type: String,
      default: "Here's some data"
    },
    color: {
      type: String,
      default: "#ffffff"
    }
  });

  const fmt = (v) => new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(v);

  const subtypes = computed(() => {
    const groups = {};
    for (const cat of Object.values(props.categorizedExpenses)) {
      const key = cat.subtype_id ?? '__none__';
      if (!groups[key]) {
        groups[key] = {
          subtypeId: cat.subtype_id,
          subtypeName: cat.subtype_name || 'No Sub Type',
          categories: [],
          total: 0,
        };
      }
      groups[key].categories.push(cat);
      groups[key].total += cat.value;
    }
    return Object.values(groups).sort((a, b) => {
      if (a.subtypeId === null) return 1;
      if (b.subtypeId === null) return -1;
      return a.subtypeName.localeCompare(b.subtypeName);
    });
  });

  const topSubtype = computed(() => {
    if (!subtypes.value.length) return null;
    return subtypes.value.reduce((best, s) => s.total > best.total ? s : best);
  });

  const bottomSubtype = computed(() => {
    if (!subtypes.value.length) return null;
    return subtypes.value.reduce((worst, s) => s.total < worst.total ? s : worst);
  });

  function groupChartData(categories) {
    const sorted = [...categories].sort((a, b) => (a.name || '').localeCompare(b.name || ''));
    return {
      labels: sorted.map(c => c.name),
      datasets: [{
        backgroundColor: sorted.map(c => c.hex_color),
        borderColor: sorted.map(() => 'transparent'),
        borderWidth: sorted.map(() => 0),
        data: sorted.map(c => c.value),
        transactions: sorted.map(c => c.transactions.map(t => ({ ...t, _cat_name: c.name }))),
      }],
    };
  }

  function bestCat(categories) {
    if (!categories.length) return null;
    return categories.reduce((best, c) => c.value > best.value ? c : best);
  }

  function worstCat(categories) {
    if (!categories.length) return null;
    return categories.reduce((worst, c) => c.value < worst.value ? c : worst);
  }

  const baseOptions = cloneDeep(expenseBreakdownOptions);
  baseOptions.plugins.title.display = false;

  const tooltipElements = reactive({});
  const hoverStates = reactive({});
  const hideTimeouts = {};

  const handleTooltipClick = (e) => {
    const item = e.target.closest('[data-date]');
    if (item) {
      sessionStorage.setItem('dashboard_scroll', window.scrollY.toString());
      router.visit(route('transactions', { start: item.dataset.date, end: item.dataset.date, chart_click: 1 }));
    }
  };

  function makeOptions(idx) {
    const opts = cloneDeep(baseOptions);
    opts.plugins.tooltip.enabled = false;
    opts.plugins.tooltip.external = function(ctx) {
      const { tooltip } = ctx;
      const el = tooltipElements[idx];
      if (!el) return;

      if (tooltip.opacity === 0) {
        if (hoverStates[idx]) return;
        if (hideTimeouts[idx]) return;
        hideTimeouts[idx] = setTimeout(() => {
          if (!hoverStates[idx] && tooltipElements[idx]) {
            tooltipElements[idx].style.opacity = '0';
            tooltipElements[idx].style.pointerEvents = 'none';
          }
          hideTimeouts[idx] = null;
        }, 200);
        return;
      }

      if (hideTimeouts[idx]) {
        clearTimeout(hideTimeouts[idx]);
        hideTimeouts[idx] = null;
      }

      const dataPoints = tooltip.dataPoints;
      if (!dataPoints.length) return;
      const dp = dataPoints[0];
      const dataIndex = dp.dataIndex;
      const dataset = dp.dataset;

      const label = ctx.chart.data.labels[dataIndex];
      const sum = dataset.data.reduce((a, b) => a + b, 0);
      const value = dataset.data[dataIndex];
      const percentage = (value * 100 / sum).toFixed(2) + '%';

      let html = `<div style="font-weight:bold;margin-bottom:4px">${label}: ${fmt(value)} - ${percentage}</div>`;

      const transactions = dataset.transactions[dataIndex];
      if (transactions && transactions.length) {
        html += `<div style="max-height:250px;overflow-y:auto">`;
        transactions.forEach((t) => {
          const dateStr = new Date(t.date).toLocaleString('us-en', { timeZone: 'utc', weekday: 'short', year: 'numeric', month: 'numeric', day: 'numeric' });
          let txt = `${dateStr} for ${fmt(t.cat_value)}`;
          if (t.note) txt += ` - ${t.note}`;
          html += `<div data-date="${t.date}" style="cursor:pointer;word-break:break-word;padding:1px 0">${txt}</div>`;
        });
        html += `</div>`;
      }

      el.innerHTML = html;

      const evt = ctx.chart._lastEvent?.native;
      let left, top;
      if (evt) {
        left = evt.clientX;
        top = evt.clientY;
      } else {
        const canvasRect = ctx.chart.canvas.getBoundingClientRect();
        left = canvasRect.left + tooltip.caretX;
        top = canvasRect.top + tooltip.caretY;
      }

      const elRect = el.getBoundingClientRect();
      if (left + elRect.width > window.innerWidth) {
        left = window.innerWidth - elRect.width - 8;
      }
      if (top + elRect.height > window.innerHeight) {
        top = window.innerHeight - elRect.height - 8;
      }
      if (top < 8) {
        top = 8;
      }

      el.style.left = left + 'px';
      el.style.top = top + 'px';
      el.style.opacity = '1';
      el.style.pointerEvents = 'auto';
    };
    return opts;
  }

  const chartDataList = computed(() =>
    subtypes.value.map(g => groupChartData(g.categories))
  );

  const topCats = computed(() =>
    subtypes.value.map(g => bestCat(g.categories))
  );

  const bottomCats = computed(() =>
    subtypes.value.map(g => worstCat(g.categories))
  );

  onMounted(() => {
    subtypes.value.forEach((_, idx) => {
      const el = tooltipElements[idx];
      if (!el) return;
      el.addEventListener('mouseenter', () => { hoverStates[idx] = true; if (hideTimeouts[idx]) { clearTimeout(hideTimeouts[idx]); hideTimeouts[idx] = null; } });
      el.addEventListener('mouseleave', () => {
        hoverStates[idx] = false;
        hideTimeouts[idx] = setTimeout(() => {
          if (!hoverStates[idx] && tooltipElements[idx]) {
            tooltipElements[idx].style.opacity = '0';
            tooltipElements[idx].style.pointerEvents = 'none';
          }
          hideTimeouts[idx] = null;
        }, 300);
      });
    });
  });
</script>

<template>
  <div class="relative w-full flex flex-col items-center">
    <div class="text-center mb-2 shrink-0">
      <div class="font-bold text-2xl" :style="{ color: props.color }">
        {{ props.title }}
      </div>
      <div v-if="topSubtype" class="text-lg" :style="{ color: props.color }">
        Highest: {{ topSubtype.subtypeName }} ({{ fmt(topSubtype.total) }})
      </div>
      <div v-if="bottomSubtype" class="text-lg mt-1" :style="{ color: props.color }">
        Lowest: {{ bottomSubtype.subtypeName }} ({{ fmt(bottomSubtype.total) }})
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 w-full">
      <div v-for="(group, idx) in subtypes" :key="group.subtypeId ?? '__none__'" class="flex flex-col items-center">
        <div class="font-bold text-lg" :style="{ color: props.color }">{{ group.subtypeName }}</div>
        <div v-if="topCats[idx]" class="text-sm" :style="{ color: props.color }">
          Highest: {{ topCats[idx].name }} ({{ fmt(topCats[idx].value) }})
        </div>
        <div v-if="bottomCats[idx]" class="text-sm" :style="{ color: props.color }">
          Lowest: {{ bottomCats[idx].name }} ({{ fmt(bottomCats[idx].value) }})
        </div>
        <div class="w-full" style="height:700px">
          <Pie :data="chartDataList[idx]" :options="makeOptions(idx)" />
        </div>
        <div
          :ref="el => { tooltipElements[idx] = el }"
          class="fixed z-50 px-3 py-2 rounded-lg shadow-lg text-sm pointer-events-none"
          style="background: rgba(0,0,0,0.8); color: white; opacity: 0; transition: opacity 0.15s;"
          @click="handleTooltipClick"
        />
      </div>
    </div>
  </div>
</template>
