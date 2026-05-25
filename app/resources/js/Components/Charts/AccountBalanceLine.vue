<script setup>
  import { inject, watch, ref, onMounted } from 'vue';
  import { router } from '@inertiajs/vue3';
  import LineChart from '@/Components/Charts/LineChart.vue';
  import { accountGrowthLinesOptions } from './chartConfig.js'
  import cloneDeep from 'lodash/cloneDeep';
  const dateFormatter = inject('dateFormatter');

  let props = defineProps({
    chartData: {
      type: Object,
      default: () => {}
    },
    accountId: {
      type: Number,
      default: null
    }
  });

  const options = cloneDeep(accountGrowthLinesOptions);
  options.plugins.title.text = "Account Balance Changes";

  const defaultChartStruct = {
    datasets: [
      {
        label:"Balance over Time",
        backgroundColor: 'rgb(75, 192, 192)',
        data: [ ],
        borderColor: 'rgb(75, 192, 192)',
        dates: []
      },
    ],
    labels: [ ]
  };

  const lineChartData = ref(structuredClone(defaultChartStruct));
  const tooltipRef = ref(null);
  const isHoveringTooltip = ref(false);
  let hideTimeout = null;

  const handleTooltipClick = (e) => {
    const item = e.target.closest('[data-date]');
    if (item) {
      sessionStorage.setItem('dashboard_scroll', window.scrollY.toString());
      const params = { start: item.dataset.date, end: item.dataset.date };
      if (props.accountId) {
        params.filter_accounts = [props.accountId];
      }
      router.visit(route('transactions', params));
    }
  };

  onMounted(() => {
    for (let date in props.chartData) {
      let dailyGrowth = props.chartData[date];
      try {
        lineChartData.value.labels.push(dateFormatter.format(new Date(date)));
      } catch (e) {
        lineChartData.value.labels.push(date);
      }
      lineChartData.value.datasets[0].data.push(dailyGrowth);
      lineChartData.value.datasets[0].dates.push(date);
    }

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

    options.plugins.tooltip = {
      enabled: false,
      external: function(ctx) {
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
      const date = dp.dataset.dates[dp.dataIndex];

      let html = `<div data-date="${date}" style="font-weight:bold;margin-bottom:4px;cursor:pointer">${tooltip.title[0]}</div>`;

      dataPoints.forEach((p) => {
        const val = new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(p.raw);
        html += `<div data-date="${p.dataset.dates[p.dataIndex]}" style="cursor:pointer;padding:1px 0">${p.dataset.label}: ${val}</div>`;
      });

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
    }
  };
  });

  const refreshChartData = () => {
    lineChartData.value = structuredClone(defaultChartStruct);
    for (let date in props.chartData) {
      let dailyGrowth = props.chartData[date];
      try {
        lineChartData.value.labels.push(dateFormatter.format(new Date(date)));
      } catch (e) {
        lineChartData.value.labels.push(date);
      }
      lineChartData.value.datasets[0].data.push(dailyGrowth);
      lineChartData.value.datasets[0].dates.push(date);
    }
  };

  watch(() => props.chartData, refreshChartData);
</script>

<template>
  <div class="relative h-full w-full">
    <LineChart
      :chart-data="lineChartData"
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
