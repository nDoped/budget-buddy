<script setup>
  import { ref, inject } from 'vue';
  import { toast } from 'vue3-toastify';
  import { useForm } from '@inertiajs/vue3'
  import InputError from '@/Components/InputError.vue';
  import InputLabel from '@/Components/InputLabel.vue';
  import TextInput from '@/Components/TextInput.vue';
  import ExpandableTable from '@/Components/ExpandableTable.vue';
  import AccountUrlLink from '@/Components/AccountUrlLink.vue';
  const formatter = inject('formatter');

  defineProps({
    accounts: {
      type: Object,
      default: () => {}
    },
    accountTypes: {
      type: Object,
      default: () => {}
    }
  });

  const hasUrl = (key) => {
    let test = fields.value.find(field => field.key === key );
    if (test.has_url) {
      return true;
    }
    return false;
  };
  const formatField = (key) => {
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
              :expand="false"
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
                    <AccountUrlLink :url="item['url']" />
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
                        v-for="(type, i) in accountTypes"
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
                </div>

                <div class="m-5">
                  <div class="mb-6">
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
                </div>

                <div class="m-5">
                  <div class="mb-6">
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
                      autofocus
                      autocomplete="initial_balance"
                    />
                    <InputError
                      :message="form.errors.initial_balance"
                      class="mt-2"
                    />
                  </div>
                </div>

                <div class="m-5">
                  <div class="mb-6">
                    <InputLabel
                      for="interest"
                      value="Interest Rate"
                    />

                    <TextInput
                      id="interest"
                      v-model="form.interest_rate"
                      type="text"
                      class="mt-1 block w-full"
                      autofocus
                      autocomplete="interest_rate"
                    />

                    <InputError
                      :message="form.errors.interest_rate"
                      class="mt-2"
                    />
                  </div>
                </div>

                <div class="m-5 w-full">
                  <div class="mb-6">
                    <InputLabel
                      for="url"
                      value="URL"
                    />

                    <TextInput
                      id="url"
                      v-model="form.url"
                      type="text"
                      class="mt-1 block w-full"
                      autofocus
                      placeholder="https://example.org"
                      autocomplete="url"
                    />
                    <InputError
                      :message="form.errors.url"
                      class="mt-2"
                    />
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
