<script setup>
  import { ref } from 'vue';
  import NavLink from '@/Components/NavLink.vue';
  import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
  const showingNavigationDropdown = ref(false);

  defineProps({
    data: {
      type: Object,
      default: () => {}
    }
  });
</script>


<template>
  <nav class="text-black bg-slate-500 border-b border-gray-100">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between h-16">
        <!-- Navigation Links -->
        <div class="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex">
          <span class="inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-3xl font-medium leading-5 dark:text-slate-900 focus:outline-none focus:text-gray-700 focus:border-gray-300 transition">Settings</span>
          <NavLink
            :href="route('settings.accounts')"
            :active="route().current('settings.accounts')"
          >
            Accounts
          </NavLink>

          <NavLink
            :href="route('settings.account_types')"
            :active="route().current('settings.account_types')"
          >
            Account Types
          </NavLink>

          <NavLink
            :href="route('settings.categories')"
            :active="route().current('settings.categories')"
          >
            Transaction Categories
          </NavLink>

          <NavLink
            :href="route('settings.category_types')"
            :active="route().current('settings.category_types')"
          >
            Transaction Category Types
          </NavLink>
        </div>
      </div>
    </div>

    <!-- Hamburger -->
    <div class="-mr-2 flex items-center sm:hidden">
      <button class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition" @click="showingNavigationDropdown = ! showingNavigationDropdown">
        <svg
          class="h-6 w-6"
          stroke="currentColor"
          fill="none"
          viewBox="0 0 24 24"
        >
          <path
            :class="{'hidden': showingNavigationDropdown, 'inline-flex': ! showingNavigationDropdown }"
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M4 6h16M4 12h16M4 18h16"
          />
          <path
            :class="{'hidden': ! showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }"
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M6 18L18 6M6 6l12 12"
          />
        </svg>
      </button>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': showingNavigationDropdown, 'hidden': ! showingNavigationDropdown}" class="sm:hidden">
      <!-- Responsive Settings Options -->
      <div class="pt-4 pb-1 border-t border-gray-200">
        <div class="mt-3 space-y-1">
          <ResponsiveNavLink
            :href="route('settings.accounts')"
            :active="route().current('settings.accounts')"
          >
            Accounts
          </ResponsiveNavLink>

          <ResponsiveNavLink :href="route('settings.categories')" :active="route().current('settings.categories')">
            Transaction Categories
          </ResponsiveNavLink>

          <ResponsiveNavLink :href="route('settings.category_types')" :active="route().current('settings.category_types')">
            Transaction Category Types
          </ResponsiveNavLink>
        </div>
      </div>
    </div>
  </nav>
</template>
