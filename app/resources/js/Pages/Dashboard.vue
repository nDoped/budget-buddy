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
          extra_expense_breakdown: Object,
          recurring_expense_breakdown: Object,
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
            :extra-expense-breakdown="props.data.extra_expense_breakdown"
            :recurring-expense-breakdown="props.data.recurring_expense_breakdown"
            :total-extra-expenses="props.data.total_extra_expenses"
            :total-recurring-expenses="props.data.total_recurring_expenses"
            :account-growth-line-data="props.data.account_growth_line_data"
            :start="props.data.start"
            :end="props.data.end"
          />
        </div>
      </div>
    </div>
  </AppLayout>
</template>
