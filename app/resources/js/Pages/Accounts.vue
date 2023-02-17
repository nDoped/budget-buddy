<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Accounts from '@/Components/Accounts.vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';

const props = defineProps({
  data: Object
});
</script>

<template>
  <Head title="Accounts" />
  <AppLayout title="Accounts">
    <template #header>
      <div>
        <ApplicationLogo class="block h-12 w-auto" />
      </div>
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
