<script setup>
  import InputLabel from '@/Components/InputLabel.vue';
  import { ref, inject } from 'vue';
  import InputError from '@/Components/InputError.vue';
  import TextInput from '@/Components/TextInput.vue';
  import ExpandableTable from '@/Components/ExpandableTable.vue';
  import { useForm } from '@inertiajs/vue3'
  const formatter = inject('formatter');



  const props = defineProps({
      accounts: Object,
      account_types: Object
  });
  function submit() {
    form.post(route('accounts.store'), {
      preserveScroll: true,
      onSuccess: () => form.reset(),
    });
  }

  const hasUrl = (key, value, item) => {
    let test = fields.value.find(field => field.key === key );
    if (test.has_url) {
      return true;
    }
    return false;
  };
  const formatField = (key, value, item) => {
    let test = fields.value.find(field => field.key === key );
    if (test.format) {
      return true;
    }
    return false;
  };


  const fields = ref([
    { key: 'name', label: 'Name', sortable: true, has_url:true },
    { key: 'type', label: 'Account Type', sortable: true },
    //{ key: 'owner', label: 'Owner' },
    { key: 'interest_rate', label: 'Interest Rate' },
    { key: 'initial_balance', label: 'Initial Balance', sortable:true, format:true },
    { key: 'url', label: 'URL' }
  ]);

  const form = useForm({
    name: null,
    type: null,
    url: null,
    interest_rate: null,
    initial_balance: null,
  });
</script>

<template>
  <div class="p-6 sm:px-20 bg-slate-700 border-b border-gray-200">
    <div class="flex flex-col">
      <div class="overflow-x-auto sm:-mx-6 lg:-mx-8">
        <h1>Accounts</h1>
      </div>

      <div class="overflow-x-auto sm:-mx-6 lg:-mx-8">
        <div class="py-2 inline-block min-w-full sm:px-6 lg:px-8">
          <div class="overflow-hidden">
            <ExpandableTable
              class="grow w-full bg-gray-800 text-slate-300"
              :items="accounts"
              :fields="fields"
            >
              <template #visible_row="{ item , value, key }">
                <div class="font-semibold text-l">
                  <template v-if="formatField(key, value, item)">
                    {{ formatter.format(value) }}
                  </template>
                  <template v-else>
                    {{ value }}
                  </template>

                  <template v-if="hasUrl(key, value, item) && item['url']">
                    <a :href="item['url']" target="_blank" class="ml-1">
                      <svg fill="#000000"
                        style="display:inline"
                        version="1.1"
                        id="Capa_1"
                        xmlns="http://www.w3.org/2000/svg"
                        xmlns:xlink="http://www.w3.org/1999/xlink"
                        width="15px" height="15px" viewBox="0 0 393.789 393.789"
                        xml:space="preserve"
                       >
                          <path d="M304.9,190.873c-5.449,0-9.865,4.422-9.865,9.864v141.033c0,17.805-14.482,32.283-32.285,32.283H52.015
                            c-17.802,0-32.284-14.479-32.284-32.283V131.037c0-17.795,14.482-32.285,32.284-32.285h141.033c5.448,0,9.866-4.412,9.866-9.865
                            c0-5.443-4.418-9.865-9.866-9.865H52.015C23.334,79.022,0,102.356,0,131.038v210.734c0,28.682,23.334,52.014,52.015,52.014H262.75
                            c28.682,0,52.016-23.332,52.016-52.014V200.737C314.766,195.295,310.348,190.873,304.9,190.873z"/>
                          <path d="M304.9,0.003c-49.016,0-88.895,39.876-88.895,88.884c0,49.02,39.879,88.895,88.895,88.895
                            c49.012,0,88.889-39.875,88.889-88.895C393.789,39.879,353.912,0.003,304.9,0.003z M304.9,158.051
                            c-38.137,0-69.164-31.021-69.164-69.164c0-38.131,31.027-69.153,69.164-69.153c38.133,0,69.158,31.022,69.158,69.153
                            C374.059,127.029,343.033,158.051,304.9,158.051z"/>
                      </svg>
                    </a>
                  </template>
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
              <div class="flex flex-wrap p-6 bg-white border-b border-gray-200">

                <div class="m-5">
                  <div class="mb-6">
                    <InputLabel for="type" value="Account Type" />
                    <select
                      id="type"
                      v-model="form.type"
                      class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"

                    >
                      <option selected value="">Select Type...</option>
                      <option v-for="type in account_types" :value="type.id">
                        {{ type.name }}
                      </option>
                    </select>
                    <InputError :message="form.errors.type" class="mt-2" />
                  </div>
                </div>

                <div class="m-5">
                  <div class="mb-6">
                    <InputLabel for="name" value="Account Name" />
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
                    <InputLabel for="init_bal" value="Initial Balance" />
                    <TextInput
                        id="init_bal"
                        v-model="form.initial_balance"
                        type="text"
                        class="mt-1 block w-full"
                        required
                        autofocus
                        autocomplete="initial_balance"
                    />
                    <InputError :message="form.errors.initial_balance" class="mt-2" />
                  </div>
                </div>

                <div class="m-5">
                  <div class="mb-6">
                    <InputLabel for="interest" value="Interest Rate" />
                    <TextInput
                        id="interest"
                        v-model="form.interest_rate"
                        type="text"
                        class="mt-1 block w-full"
                        autofocus
                        autocomplete="interest_rate"
                    />
                    <InputError :message="form.errors.interest_rate" class="mt-2" />
                  </div>
                </div>

                <div class="m-5 w-full">
                  <div class="mb-6">
                    <InputLabel for="url" value="URL" />
                    <TextInput
                        id="url"
                        v-model="form.url"
                        type="text"
                        class="mt-1 block w-full"
                        autofocus
                        placeholder="https://example.org"
                        autocomplete="url"
                    />
                    <InputError :message="form.errors.url" class="mt-2" />
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
                  Add Account
                </button>
              </div>
            </form>

          </div>
        </div>
      </div>
    </div>
  </div>
</template>
