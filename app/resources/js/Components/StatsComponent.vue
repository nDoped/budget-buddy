<template>
  <div class="w-full">
    <h3 class="text-lg font-medium leading-6 text-surface-900">
      {{ last }}
    </h3>
    <dl class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-6">
      <div
        v-for="(stat,index) in stats"
        :key="index"
      >
        <div class="flex flex-col overflow-hidden bg-zinc-500 dark:bg-slate-900 rounded-lg px-4 py-5 shadow sm:p-6">
          <dt class="order-last truncate text-sm font-medium text-surface-500">
            {{ stat.title }}
          </dt>
          <dd class="mb-1 text-3xl bg-zinc-500 dark:bg-slate-900 font-semibold text-primary-600">
            <span class="tabular-nums">{{ stat.value }}</span>
          </dd>
        </div>
      </div>
    </dl>
  </div>
</template>

<script>
  export default {
    name: 'StatsComponent',
    props: {
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
    },
  };
</script>
