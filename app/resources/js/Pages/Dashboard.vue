<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Welcome from '@/Components/Welcome.vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
const props = defineProps({
    data: Object
});
</script>

<template>
  <AppLayout title="Dashboard">
    <template #header>
      <div class="bg-slate-600 overflow-hidden shadow-xl sm:rounded-lg">
        <ApplicationLogo class="block h-12 w-auto" />
      </div>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
          <Welcome
            :assets="data.asset_accounts"
            :debts="data.debt_accounts"
            :start="data.start"
            :end="data.end"
          />
        </div>
      </div>
    </div>
  </AppLayout>
</template>
