<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Transactions from '@/Components/Transactions.vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';

const props = defineProps({
    data: Object
});
</script>

<template>
  <Head title="Transactions" />
  <AppLayout title="Transactions">
    <template #header>
      <div>
        <ApplicationLogo class="block h-12 w-auto" />
      </div>
    </template>
    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-slate-700 overflow-hidden shadow-xl sm:rounded-lg">
          <Transactions
            :transactions='data.transactions_in_range'
            :accounts='data.accounts'
            :start='data.start'
            :end='data.end'
          />
        </div>
      </div>
    </div>
  </AppLayout>
</template>
