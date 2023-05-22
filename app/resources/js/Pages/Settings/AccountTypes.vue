<script setup>
  import AppLayout from '@/Layouts/AppLayout.vue';
  import SettingsNavMenu from '@/Components/SettingsNavMenu.vue';
  import AccountTypes from '@/Components/AccountTypes.vue';

  defineProps({
    data: {
      type: Object,
      default: () => {}
    }
  });
</script>

<template>
  <AppLayout title="Settings - Account Types">
    <div class="max-w-8xl mx-auto py-10 sm:px-6 lg:px-8">
      <SettingsNavMenu />

      <div class="overflow-x-auto sm:-mx-6 lg:-mx-8">
        <div class="py-2 inline-block min-w-full sm:px-6 lg:px-8">
          <AccountTypes :account-types="data.account_types" />
        </div>
      </div>
    </div>
  </AppLayout>
</template>
