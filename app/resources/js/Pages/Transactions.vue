<script setup>
  import { Head } from '@inertiajs/vue3';
  import AppLayout from '@/Layouts/AppLayout.vue';
  import TransActions from '@/Components/TransActions.vue';

  defineProps({
    data: {
      type: Object,
      default: () => {}
    }
  });
</script>

<template>
  <Head title="Transactions" />
  <AppLayout title="Transactions">
    <div class="py-12">
      <div class="max-w-11xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-slate-200 dark:bg-slate-700 shadow-xl sm:rounded-lg">
          <TransActions
            :transactions="data.transactions_in_range"
            :categories="data.categories"
            :category-types="data.category_types"
            :accounts="data.accounts"
            :start="data.start"
            :end="data.end"
          />
        </div>
      </div>
    </div>
  </AppLayout>
</template>
