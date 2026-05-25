<script setup>
  import {
    watch,
    ref,
    onMounted
  } from 'vue';
  import { router } from '@inertiajs/vue3';
  import PieChart from '@/Components/Charts/PieChart.vue';
  import { expenseBreakdownOptions } from './chartConfig.js'
  import cloneDeep from 'lodash/cloneDeep';

  let props = defineProps({
    categorizedExpenses: {
      type: Object,
      default: () => {}
    },
    title: {
      type: String,
      default: () => "Here's some data"
    },
    color: {
      type: String,
      default: () => "#ffffff"
    }
  });

  const defaultChartStruct = {
    labels: [],
    datasets: [
      {
        backgroundColor: [],
        data: [],
        transactions: []
      }
    ],
  };

  const sortObj = (obj) => {
    return Object.keys(obj).sort().reduce(function (result, key) {
      result[key] = obj[key];
      return result;
    }, {});
  };

  const pieChartData = ref(structuredClone(defaultChartStruct));
  watch(() => props.categorizedExpenses, () => {
    pieChartData.value = structuredClone(defaultChartStruct);
    for (let id in sortObj(props.categorizedExpenses)) {
      pieChartData.value.datasets[0].data.push(props.categorizedExpenses[id].value);
      pieChartData.value.datasets[0].backgroundColor.push(props.categorizedExpenses[id].hex_color);
      pieChartData.value.datasets[0].transactions.push(props.categorizedExpenses[id].transactions);
      pieChartData.value.labels.push(props.categorizedExpenses[id].name);
    }
  });

  const options = cloneDeep(expenseBreakdownOptions);
  const tooltipRef = ref(null);
  const isHoveringTooltip = ref(false);
  let hideTimeout = null;

  const handleTooltipClick = (e) => {
    const item = e.target.closest('[data-date]');
    if (item) {
      sessionStorage.setItem('dashboard_scroll', window.scrollY.toString());
      router.visit(route('transactions', { start: item.dataset.date, end: item.dataset.date, chart_click: 1 }));
    }
  };

  onMounted(() => {
    for (let id in sortObj(props.categorizedExpenses)) {
      pieChartData.value.datasets[0].data.push(props.categorizedExpenses[id].value);
      pieChartData.value.datasets[0].backgroundColor.push(props.categorizedExpenses[id].hex_color);
      pieChartData.value.datasets[0].transactions.push(props.categorizedExpenses[id].transactions);
      pieChartData.value.labels.push(props.categorizedExpenses[id].name);
    }
    options.plugins.title.text = props.title;
    options.plugins.title.font = {
      weight: 'bold',
      size:24
    };
    options.plugins.title.color = props.color;

    const tEl = tooltipRef.value;
    if (tEl) {
      tEl.addEventListener('mouseenter', () => {
        isHoveringTooltip.value = true;
        if (hideTimeout) {
          clearTimeout(hideTimeout);
          hideTimeout = null;
        }
      });
      tEl.addEventListener('mouseleave', () => {
        isHoveringTooltip.value = false;
        hideTimeout = setTimeout(() => {
          if (!isHoveringTooltip.value && tooltipRef.value) {
            tooltipRef.value.style.opacity = '0';
            tooltipRef.value.style.pointerEvents = 'none';
          }
          hideTimeout = null;
        }, 300);
      });
    }

    options.plugins.tooltip.enabled = false;
    options.plugins.tooltip.external = function(ctx) {
      const { tooltip } = ctx;
      const el = tooltipRef.value;
      if (!el) return;

      if (tooltip.opacity === 0) {
        if (isHoveringTooltip.value) return;
        if (hideTimeout) return;
        hideTimeout = setTimeout(() => {
          if (!isHoveringTooltip.value && tooltipRef.value) {
            tooltipRef.value.style.opacity = '0';
            tooltipRef.value.style.pointerEvents = 'none';
          }
          hideTimeout = null;
        }, 200);
        return;
      }

      if (hideTimeout) {
        clearTimeout(hideTimeout);
        hideTimeout = null;
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
      const displayVal = new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(value);

      let html = `<div style="font-weight:bold;margin-bottom:4px">${label}: ${displayVal} - ${percentage}</div>`;

      const transactions = dataset.transactions[dataIndex];
      if (transactions && transactions.length) {
        transactions.forEach((t) => {
          const catVal = new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(t.cat_value);
          const dateStr = new Date(t.date).toLocaleString('us-en', { timeZone: 'utc', weekday: 'short', year: 'numeric', month: 'numeric', day: 'numeric' });
          let txt = `${dateStr} for ${catVal}`;
          if (t.note) txt += ` - ${t.note}`;
          html += `<div data-date="${t.date}" style="cursor:pointer;white-space:nowrap;padding:1px 0">${txt}</div>`;
        });
      }

      el.innerHTML = html;

      const canvasRect = ctx.chart.canvas.getBoundingClientRect();
      let left = canvasRect.left + tooltip.caretX;
      let top = canvasRect.top + tooltip.caretY;

      const elRect = el.getBoundingClientRect();
      if (left + elRect.width > window.innerWidth) {
        left = window.innerWidth - elRect.width - 8;
      }
      if (top + elRect.height > window.innerHeight) {
        top = window.innerHeight - elRect.height - 8;
      }

      el.style.left = left + 'px';
      el.style.top = top + 'px';
      el.style.opacity = '1';
      el.style.pointerEvents = 'auto';
    };
  });
</script>

<template>
  <div class="relative h-full w-full">
    <PieChart
      :chart-data="pieChartData"
      :chart-options="options"
    />
    <div
      ref="tooltipRef"
      class="fixed z-50 px-3 py-2 rounded-lg shadow-lg text-sm pointer-events-none"
      style="background: rgba(0,0,0,0.8); color: white; opacity: 0; transition: opacity 0.15s;"
      @click="handleTooltipClick"
    ></div>
  </div>
</template>
<style>
.chart-wrapper {
  display: inline-block;
  position: relative;
  width: 100%;
}
</style>
