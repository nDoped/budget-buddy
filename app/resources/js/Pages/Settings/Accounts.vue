<script setup>
  import AppLayout from '@/Layouts/AppLayout.vue';
  import SettingsNavMenu from '@/Components/SettingsNavMenu.vue';
  import { toast } from 'vue3-toastify';
  import AccountTable from '@/Components/AccountTable.vue';
  import { useForm } from '@inertiajs/vue3'
  import InputError from '@/Components/InputError.vue';
  import InputLabel from '@/Components/InputLabel.vue';
  import PrimaryButton from '@/Components/PrimaryButton.vue';
  import TextInput from '@/Components/TextInput.vue';

  defineProps({
    data: {
      type: Object,
      default: () => {}
    }
  });

  const success = () => {
    toast.success('Account Created!');
    form.reset();
  };

  const form = useForm({
    name: null,
    type: null,
    url: null,
    interest_rate: null,
    initial_balance: null,
  });
  function submit() {
    /* global route */
    form.post(route('accounts.store'), {
      preserveScroll: true,
      onSuccess: success,
    });
  }
</script>

<template>
  <AppLayout title="Settings - Accounts">
    <div class="max-w-8xl mx-auto py-10 sm:px-6 lg:px-8">
      <SettingsNavMenu />

      <div class="overflow-x-auto sm:-mx-6 lg:-mx-8">
        <div class="py-2 inline-block min-w-full sm:px-6 lg:px-8">
          <div class="overflow-hidden">
            <form @submit.prevent="submit">
              <div class="flex flex-wrap p-6 bg-slate-500 border-b border-gray-200">
                <div class="m-4">
                  <InputLabel
                    for="type"
                    value="Account Type"
                  />
                  <select
                    id="type"
                    v-model="form.type"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                  >
                    <option
                      selected
                      value=""
                    >
                      Select Type...
                    </option>

                    <option
                      v-for="(type, i) in data.account_types"
                      :key="i"
                      :value="type.id"
                    >
                      {{ type.name }}
                    </option>
                  </select>
                  <InputError
                    :message="form.errors.type"
                    class="mt-2"
                  />
                </div>

                <div class="m-4">
                  <InputLabel
                    for="name"
                    value="Account Name"
                  />
                  <TextInput
                    id="name"
                    v-model="form.name"
                    type="text"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                    required
                  />
                  <InputError
                    :message="form.errors.name"
                    class="mt-2"
                  />
                </div>

                <div class="m-4">
                  <InputLabel
                    for="init_bal"
                    value="Initial Balance"
                  />
                  <TextInput
                    id="init_bal"
                    v-model="form.initial_balance"
                    type="text"
                    class="mt-1 block w-full"
                    required
                    autocomplete="initial_balance"
                  />
                  <InputError
                    :message="form.errors.initial_balance"
                    class="mt-2"
                  />
                </div>

                <div class="m-4">
                  <InputLabel
                    for="interest"
                    value="Interest Rate"
                  />

                  <TextInput
                    id="interest"
                    v-model="form.interest_rate"
                    type="text"
                    class="mt-1 block w-full"
                    autocomplete="interest_rate"
                  />

                  <InputError
                    :message="form.errors.interest_rate"
                    class="mt-2"
                  />
                </div>

                <div class="m-4">
                  <InputLabel
                    for="url"
                    value="URL"
                  />

                  <TextInput
                    id="url"
                    v-model="form.url"
                    type="text"
                    class="mt-1 block w-full"
                    placeholder="https://example.org"
                    autocomplete="url"
                  />
                  <InputError
                    :message="form.errors.url"
                    class="mt-2"
                  />
                </div>
              </div>

              <div class="flex flex-wrap p-6 bg-slate-500 border-gray-200">
                <PrimaryButton
                  type="submit"
                  class="ml-3"
                  :disabled="form.processing"
                  :class="{ 'opacity-25': form.processing }"
                >
                  Add Account
                </PrimaryButton>
              </div>
            </form>
          </div>
        </div>
      </div>

      <div class="overflow-x-auto">
        <AccountTable :accounts="data.accounts" />
      </div>
    </div>
  </AppLayout>
</template>
