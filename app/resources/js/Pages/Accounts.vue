<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Accounts from '@/Components/Accounts.vue';

const props = defineProps({
  data: Array
});

</script>

<template>
  <Head title="Accounts" />
  <AppLayout title="Accounts">
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Accounts
      </h2>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-black overflow-hidden shadow-xl sm:rounded-lg">
          <Accounts
            :accounts='data.accounts'
            :account_types='data.types'
          />
        </div>
      </div>
    </div>
  </AppLayout>

</template>


