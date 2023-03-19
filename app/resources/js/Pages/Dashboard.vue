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
          housing_expense_breakdown: Object,
          utility_expense_breakdown: Object,
          primary_income_breakdown: Object,
          extra_income_breakdown: Object,
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
            :housing-expense-breakdown="props.data.housing_expense_breakdown"
            :utility-expense-breakdown="props.data.utility_expense_breakdown"
            :primary-income-breakdown="props.data.primary_income_breakdown"
            :extra-income-breakdown="props.data.extra_income_breakdown"
            :total-extra-expenses="props.data.total_extra_expenses"
            :total-recurring-expenses="props.data.total_recurring_expenses"
            :total-housing-expenses="props.data.total_housing_expenses"
            :total-utility-expenses="props.data.total_utility_expenses"
            :total-primary-income="props.data.total_primary_income"
            :total-extra-income="props.data.total_extra_income"
            :account-growth-line-data="props.data.account_growth_line_data"
            :start="props.data.start"
            :end="props.data.end"
          />
        </div>
      </div>
    </div>
  </AppLayout>
</template>
