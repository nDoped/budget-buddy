<script setup>
  import AppLayout from '@/Layouts/AppLayout.vue';
  import DashBoard from '@/Components/DashBoard.vue';

  const props = defineProps({
    data: {
      type: Object,
      default: () => {
        return {
          asset_accounts: Object,
          debt_accounts: Object,
          total_economic_growth: Object,
          category_totals: Object,
          account_growth_line_data: Object,
          start: String,
          end: String
        }
      }
    }
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
            :total-economic-growth="props.data.total_economic_growth"
            :categorized-expenses="props.data.category_totals"
            :account-growth-line-data="props.data.account_growth_line_data"
            :start="props.data.start"
            :end="props.data.end"
          />
        </div>
      </div>
    </div>
  </AppLayout>
</template>
