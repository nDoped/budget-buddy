<script setup>
  import InputLabel from '@/Components/InputLabel.vue';
  import { ref } from 'vue';
  import InputError from '@/Components/InputError.vue';
  import TextInput from '@/Components/TextInput.vue';
  import { useForm } from '@inertiajs/vue3'
  import ExpandableTable from '@/Components/ExpandableTable.vue';

  const props = defineProps({
      account_types: Object
  });
  function submit() {
    form.post(route('account_types.store'), {
      preserveScroll: true,
      onSuccess: () => form.reset(),
    });
  }

  const fields = ref([
    { key: 'name', label: 'Name' },
    { key: 'asset', label: 'Asset/Debt'},
  ]);

  const form = useForm({
    name: null,
    asset: null,
  });
</script>

<template>
  <div class="p-6 sm:px-20 bg-slate-700 border-b border-gray-200">
    <div class="flex flex-col">
      <div class="overflow-x-auto sm:-mx-6 lg:-mx-8">
        <h1>Account Types</h1>
      </div>
      <div class="overflow-x-auto sm:-mx-6 lg:-mx-8">
        <div class="py-2 inline-block min-w-full sm:px-6 lg:px-8">
          <div class="overflow-hidden">
          <ExpandableTable
            class="grow w-full bg-gray-800 text-slate-300"
            :items="account_types"
            :fields="fields"
          >
            <template #visible_row="{ item , value, key }">
              <div class="font-semibold text-l">
                {{ value }}
              </div>
            </template>
          </ExpandableTable>
          </div>
        </div>
      </div>
    </div>

    <div class="flex flex-col">
      <div class="overflow-x-auto sm:-mx-6 lg:-mx-8">
        <h1>Create One</h1>
      </div>
      <div class="overflow-x-auto sm:-mx-6 lg:-mx-8">
        <div class="py-2 inline-block min-w-full sm:px-6 lg:px-8">
          <div class="overflow-hidden">
            <form @submit.prevent="submit">
              <div class="flex flex-row p-6 bg-white border-b border-gray-200">
                <div class="m-5">
                  <div class="mb-6">
                    <InputLabel for="name" value="Name" />
                    <TextInput
                        id="name"
                        v-model="form.name"
                        type="text"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                        required
                    />
                    <InputError :message="form.errors.name" class="mt-2" />
                  </div>
                </div>

                <div class="m-5">
                  <div class="mb-6">
                    <InputLabel for="asset" value="asset" />
                    <select
                      id="asset"
                      v-model="form.asset"
                      class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"

                    >
                      <option selected="selected" value=true>Asset</option>
                      <option value=false>Debt</option>
                    </select>
                    <InputError :message="form.errors.type" class="mt-2" />
                  </div>
                </div>
              </div>

              <div class="m-5">
                <button
                  type="submit"
                  class="text-white bg-gray-600  focus:ring-4 focus:outline-none font-medium rounded-lg text-sm w-full sm:w-auto px-5 py-2.5 text-center "
                  :disabled="form.processing"
                  :class="{ 'opacity-25': form.processing }"
                >
                  Add Account Type
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
