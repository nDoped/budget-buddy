<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Accounts from '@/Components/Accounts.vue';
import AccountTypes from '@/Components/AccountTypes.vue';
import SectionBorder from '@/Components/SectionBorder.vue';

const props = defineProps({
  data: Object
});
</script>


<template>
  <AppLayout title="Settings">
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Settings
      </h2>
    </template>

    <div>
      <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
        <Accounts
          :accounts='data.accounts'
          :account_types='data.account_types'
        />

        <SectionBorder />

        <AccountTypes
          :account_types='data.account_types'
        />

        <SectionBorder />

        <!--
        <div v-if="$page.props.jetstream.canUpdatePassword">
          <UpdatePasswordForm class="mt-10 sm:mt-0" />

          <SectionBorder />
        </div>

        <div v-if="$page.props.jetstream.canManageTwoFactorAuthentication">
          <TwoFactorAuthenticationForm
            :requires-confirmation="confirmsTwoFactorAuthentication"
            class="mt-10 sm:mt-0"
          />

          <SectionBorder />
        </div>

        <LogoutOtherBrowserSessionsForm :sessions="sessions" class="mt-10 sm:mt-0" />

        <template v-if="$page.props.jetstream.hasAccountDeletionFeatures">
          <SectionBorder />

          <DeleteUserForm class="mt-10 sm:mt-0" />
        </template>
        -->
      </div>
    </div>
  </AppLayout>
</template>
