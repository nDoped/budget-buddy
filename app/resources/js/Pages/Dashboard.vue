<script setup>
  import AppLayout from '@/Layouts/AppLayout.vue';
  import DashBoard from '@/Components/DashBoard.vue';

  const props = defineProps({
    data: {
      type: Object,
      default: () => {}
    }
  });
</script>

<template>
  <AppLayout title="Dashboard">
    <div class="py-12">
      <div class="max-w-11xl mx-auto sm:px-6 lg:px-8">
        <div class=" bg-zinc-300 dark:text-white dark:bg-slate-700 overflow-hidden shadow-xl sm:rounded-lg">
          <DashBoard
            :assets="props.data.asset_accounts"
            :debts="props.data.debt_accounts"
            :total-economic-growth="props.data.total_economic_growth"
            :category-type-breakdowns="props.data.category_type_breakdowns"
            :account-growth-line-data="props.data.account_growth_line_data"
            :start="props.data.start"
            :end="props.data.end"
            :recurring-transactions="props.data.recurring_transactions"
          />
        </div>
      </div>
    </div>
  </AppLayout>
</template>
