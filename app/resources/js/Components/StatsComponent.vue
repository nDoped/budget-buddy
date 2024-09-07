<template>
  <div class="gap-5 p-6">
    <h3 class="text-lg font-medium leading-6 text-surface-900">
      {{ last }}
    </h3>
    <dl class="mt-5 flex flex-row justify-stretch flex-wrap gap-5">
      <div
        v-for="(stat,index) in stats"
        :key="index"
      >
        <div class="flex flex-col overflow-hidden bg-zinc-500 dark:bg-slate-900 rounded-lg px-4 py-5 shadow sm:p-6">
          <dt class="order-last truncate text-sm font-medium text-surface-500">
            {{ stat.title }}
          </dt>
          <dd
            class="mb-1 text-3xl bg-zinc-500 dark:bg-slate-900 font-semibold text-primary-600"
            :class="stat.class"
          >
            <span class="tabular-nums" :style="getStyle(stat)">{{ stat.value }}</span>
          </dd>
        </div>
      </div>
    </dl>
  </div>
</template>

<script setup>
  const getStyle = (stat) => {
    return `color: ${stat.hex_color}`
  };

  defineProps({
    stats: {
      type: Array,
      default() {
        return [];
      },
    },
    last: {
      type: String,
      default() {
        return 'Last 30 Days';
      },
    },
  });
</script>
