<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3'
import { computed, reactive, watch, ref } from 'vue';
import { sort } from 'fast-sort'
const sortBy = ref(null);
const sortDesc = ref(null);

const props = defineProps({
  transactions: Array,
  accounts: Array
});

const fields = ref([
  { key: 'id', label: 'ID', sortable: true },
  { key: 'transaction_date', label: 'Transaction Date', sortable: true },
  { key: 'credit', label: 'Credit/Debit', sortable: true },
  { key: 'amount', label: 'Amount', sortable:true },
  { key: 'account', label: 'Account', sortable:true },
  { key: 'account_type', label: 'Account Type', sortable:true },
  { key: 'ident', label: 'Bank Identifier' },
  { key: 'note', label: 'Note' },
]);

const sortedItems = computed(() => {
  const { transactions } = props;
  if (sortDesc.value === null) return transactions;

  if (sortDesc.value) {
    return sort(transactions).desc(sortBy.value);
  } else {
    return sort(transactions).asc(sortBy.value);
  }
});

const setSort = (key) => {
  if (sortBy.value === key) {
    if (sortDesc.value === true) sortDesc.value = null;
    else if (sortDesc.value === false) sortDesc.value = true;
    else sortDesc.value = false;
  } else {
    sortBy.value = key;
    sortDesc.value = false;
  }
};
const perPage = ref(10);
const pagination = reactive({
  currentPage: 1,
  perPage: perPage,
  totalPages: computed(() =>
    Math.ceil(props.transactions.length / pagination.perPage)
  ),
});

const paginatedItems = computed(() => {
  const { currentPage, perPage } = pagination;
  const start = (currentPage - 1) * perPage;
  const stop = start + perPage;

  return sortedItems.value.slice(start, stop);
});

watch(
  () => pagination.totalPages,
  () => (pagination.currentPage = 1)
);


function submit() {
  form.post(route('transactions.store'), {
    preserveScroll: true,
    onSuccess: () => form.reset(),
  });
}

const form = useForm({
  transaction_date: null,
  amount: null,
  credit: null,
  account: null,
  note: null,
  path: null,
  bank_identifier: null
});
</script>

<template>
  <div class="p-6 sm:px-20 bg-slate-700 border-b border-gray-200">
    <div>
        <ApplicationLogo class="block h-12 w-auto" />
    </div>

    <div >
      <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
          <div class="bg-slate-500 overflow-hidden shadow-sm sm:rounded-lg">
            <form @submit.prevent="submit">
              <div class="flex flex-row p-6 bg-slate-500 border-b border-gray-200">
                <div class="m-5">
                  <div class="mb-6">
                    <InputLabel for="date" value="Transaction Date" />
                    <input
                      id="date"
                      type="date"
                      v-model="form.transaction_date"
                      class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                      placeholder=""

                    />
                    <InputError :message="form.errors.transaction_date" class="mt-2" />
                  </div>
                </div>

                <div class="m-5">
                  <div class="mb-6">
                    <InputLabel for="amount" value="Amount" />
                    <input
                      type="number"
                      min="0"
                      step="any"
                      v-model="form.amount"
                      class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"

                    />
                    <InputError :message="form.errors.amount" class="mt-2" />
                  </div>
                </div>

                <div class="m-5">
                  <div class="mb-6">
                    <InputLabel for="credit" value="Credit/Debit" />
                    <select
                      id="credit"
                      v-model="form.credit"
                      class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"

                    >
                      <option value=true>Credit</option>
                      <option value=false>Debit</option>
                    </select>
                    <InputError :message="form.errors.credit" class="mt-2" />
                  </div>
                </div>

                <div class="m-5">
                  <div class="mb-6">
                    <InputLabel for="account" value="Account" />
                    <select
                      id="account"
                      v-model="form.account"
                      class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"

                    >
                      <option selected value="">Select Account...</option>
                      <option v-for="account in accounts" :value="account.id">
                        {{ account.name }}
                      </option>
                    </select>
                    <InputError :message="form.errors.account" class="mt-2" />
                  </div>
                </div>
                <div class="m-5">
                  <div class="mb-6">
                    <InputLabel for="note" value="Note" />
                    <TextInput
                        id="note"
                        v-model="form.note"
                        type="text"
                        class="mt-1 block w-full"
                        autofocus
                        autocomplete="note"
                    />
                    <InputError :message="form.errors.note" class="mt-2" />
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
                  Add Transaction
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <div class="flex flex-col">
      <div class="overflow-x-auto sm:-mx-6 lg:-mx-8">
        <div class="py-2 inline-block min-w-full sm:px-6 lg:px-8">
          <div class="overflow-hidden">
            <div style="text-align: right">
              <button
                :disabled="pagination.currentPage <= 1"
                @click="pagination.currentPage--"
              >
                &lt;&lt;
              </button>
              Page {{ pagination.currentPage }} of {{ pagination.totalPages }}
              <button
                :disabled="pagination.currentPage >= pagination.totalPages"
                @click="pagination.currentPage++"
              >
                &gt;&gt;
              </button>
            </div>

            <div style="text-align: right">
              Results per page
              <button class="mx-2" @click="perPage = 5">5</button>
              <button class="mx-2" @click="perPage = 10">10</button>
              <button class="mx-2" @click="perPage = 20">20</button>
            </div>

            <table class="min-w-full">
              <thead>
                <tr>
                  <template v-for="{ key, label, sortable } in fields" :key="key">
                    <th v-if="sortable" @click="setSort(key)" class="sortable">
                      {{ label }}
                      <template v-if="sortBy === key">
                        <span v-if="sortDesc === true">↑</span>
                        <span v-else-if="sortDesc === false">↓</span>
                      </template>
                    </th>
                    <th v-else>
                      {{ label }}
                    </th>
                  </template>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in paginatedItems" :key="item.uuid" :class="{ 'bg-gray-100':true, 'border-b': true, 'credit':item.credit === 'Credit', 'debit': item.credit === 'Debit'}">
                  <td v-for="{ key } in fields" :key="key" class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                    <slot :name="`cell(${key})`" :value="item[key]" :item="item">
                      {{ item[key] }}
                    </slot>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style>
.credit {
  background-color: rgba(109, 232, 144, 0.3);
}
.debit {
  background-color: rgba(224, 58, 58, 0.1);
}
th.sortable {
  cursor: pointer;
}
</style>

