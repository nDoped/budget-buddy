<script setup>
  import { Head } from '@inertiajs/vue3';
  import AppLayout from '@/Layouts/AppLayout.vue';
  import TransactionsForm from '@/Components/TransactionsForm.vue';

  defineProps({
    accounts: {
      type: Array,
      default: () => []
    },
    categories: {
      type: Array,
      default: () => []
    },
    categoryTypes: {
      type: Array,
      default: () => []
    }
  });
</script>

<template>
  <Head title="Create a Transaction" />
  <AppLayout title="CreateTransaction">
    <div class="py-12">
      <div class="max-w-11xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-slate-200 dark:bg-slate-700 shadow-xl sm:rounded-lg">
          <TransactionsForm
            :accounts="accounts"
            :categories="categories"
            :category-types="categoryTypes"
          />
        </div>
      </div>
    </div>
  </AppLayout>
</template>
