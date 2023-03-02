<script setup>
  import AppLayout from '@/Layouts/AppLayout.vue';
  import Accounts from '@/Components/Accounts.vue';
  import AccountTypes from '@/Components/AccountTypes.vue';
  import SectionBorder from '@/Components/SectionBorder.vue';
  import SettingsNavMenu from '@/Components/SettingsNavMenu.vue';

  const props = defineProps({
    data: {
      type: Object,
      default: () => {}
    }
  });
</script>


<template>
  <AppLayout title="Settings - Accounts">
    <div class="max-w-11xl mx-auto py-10 sm:px-6 lg:px-8">
      <SettingsNavMenu />
      <Accounts
        :accounts="data.accounts"
        :account_types="data.account_types"
      />
      <SectionBorder />

      <AccountTypes
        :account_types="data.account_types"
      />
    </div>
  </AppLayout>
</template>
