<script setup>
  import { Head, Link } from '@inertiajs/vue3';
  import ApplicationLogo from '@/Components/ApplicationLogo.vue';

  defineProps({
    canLogin: {
      type: Boolean,
      default: () => false
    },
    canRegister: {
      type: Boolean,
      default: () => false
    },

    laravelVersion: {
      type: String,
      default: () => ''
    },
    phpVersion: {
      type: String,
      default: () => ''
    }
  });
</script>

<template>
  <Head title="My Kick Ass Budget" />

  <div class="relative flex items-top justify-center min-h-screen bg-gray-100 dark:bg-gray-900 sm:items-center sm:pt-0">
    <div
      v-if="canLogin"
      class="fixed top-0 right-0 px-6 py-4"
    >
      <Link
        v-if="$page.props.user"
        :href="route('dashboard')"
        class="text-sm text-gray-700 dark:text-gray-500 underline"
      >
        Dashboard
      </Link>

      <template v-else>
        <Link
          :href="route('login')"
          class="text-sm text-gray-700 dark:text-gray-500 underline"
        >
          Log in
        </Link>

        <Link
          v-if="canRegister"
          :href="route('register')"
          class="ml-4 text-sm text-gray-700 dark:text-gray-500 underline"
        >
          Register
        </Link>
      </template>
    </div>

    <ApplicationLogo />
  </div>
</template>
