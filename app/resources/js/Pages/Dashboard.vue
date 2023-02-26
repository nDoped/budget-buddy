<script setup>
  import AppLayout from '@/Layouts/AppLayout.vue';
  import DashBoard from '@/Components/DashBoard.vue';

  const props = defineProps({
    data: Object
  });
</script>

<template>
  <AppLayout title="Dashboard">
    <div class="py-12">
      <div class="max-w-11xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
          <DashBoard
            :assets="props.data.asset_accounts"
            :debts="props.data.debt_accounts"
            :totalEconomicGrowth="props.data.total_economic_growth"
            :categorizedExpenses="props.data.category_totals"
            :accountGrowthLineData="props.data.account_growth_line_data"
            :start="props.data.start"
            :end="props.data.end"
          />
        </div>
      </div>
    </div>
  </AppLayout>
</template>
