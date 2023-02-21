<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Welcome from '@/Components/Welcome.vue';
const props = defineProps({
    data: Object
});
</script>

<template>
  <AppLayout title="Dashboard">
    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
          <Welcome
            :assets="props.data.asset_accounts"
            :debts="props.data.debt_accounts"
            :totalEconomicGrowth="props.data.total_economic_growth"
            :categorizedExpenses="props.data.category_totals"
            :start="props.data.start"
            :end="props.data.end"
          />
        </div>
      </div>
    </div>
  </AppLayout>
</template>
