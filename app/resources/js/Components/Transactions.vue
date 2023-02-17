<script setup>
import { computed, reactive, watch, ref, onMounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import { sort } from 'fast-sort'
import { useForm } from '@inertiajs/vue3'
import TransactionsForm from '@/Components/TransactionsForm.vue';
import InputDate from '@/Components/InputDate.vue';
import DateFilter from '@/Components/DateFilter.vue';
import InputLabel from '@/Components/InputLabel.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import Checkbox from '@/Components/Checkbox.vue';
const sortBy = ref(null);
const sortDesc = ref(null);
const transBeingDeleted = ref(null);

const confirmTransactionDeletion = (trans) => {
    transBeingDeleted.value = trans;
};

let props = defineProps({
  transactions: Array,
  accounts: Array,
  start: String,
  end: String,
});
const startDateEl = ref();
const endDateEl = ref();
const transStart = ref(props.start);
const transEnd = ref(props.end);

const deleteTransactionForm = useForm({});
const deleteTransaction = () => {
    deleteTransactionForm.delete(route('transactions.destroy', { id: transBeingDeleted.value, transStart: transStart.value, transEnd: transEnd.value }), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => (transBeingDeleted.value = null),
    });
}

const filterTransactionsForm = useForm({});
const filterEventHandler = (data) => {
    filterTransactionsForm.get(route('transactions', data.value), {
        preserveScroll: true,
        preserveState: true,
    });
}
const showAll = () => {
    filterTransactionsForm.get(route('transactions', { show_all: true }), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
          transStart.value = null;
          transEnd.value = null;
        }
    });
}

const fields = ref([
  //{ key: 'id', label: 'ID', sortable: true },
  { key: 'transaction_date', label: 'Transaction Date', sortable: true },
  { key: 'asset_text', label: 'Credit/Debit', sortable: true },
  { key: 'amount', label: 'Amount', sortable:true },
  { key: 'account', label: 'Account', sortable:true },
  //{ key: 'account_type', label: 'Account Type', sortable:true },
  { key: 'ident', label: 'Bank Identifier' },
  { key: 'note', label: 'Note' },
]);

onMounted(() => {
  transStart.value = props.start;
  transEnd.value = props.end;
});

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
    sortDesc.value = ! sortDesc.value;
  } else {
    sortBy.value = key;
    sortDesc.value = false;
  }
};
const perPage = ref(20);
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

</script>

<template>
  <div class="p-6 sm:px-20 bg-slate-700 border-b border-gray-200">
    <TransactionsForm :accounts="accounts" :startDate="transStart" :endDate="transEnd"/>

    <div class="grid grid-cols-2 gap-4">
      <div class="overflow-x-auto sm:-mx-6 lg:-mx-8">
        <div class="py-2 min-w-full sm:px-6 lg:px-8">

          <DateFilter
            :start="transStart"
            :end="transEnd"
            :processing="filterTransactionsForm.processing"
            @filter="filterEventHandler"
          >
            Filter
          </DateFilter>

          <button
            class="text-white bg-gray-600  focus:ring-4 focus:outline-none font-medium rounded-lg text-sm w-full sm:w-auto px-5 py-2.5 text-center "
            :class="{ 'opacity-25': filterTransactionsForm.processing }"
            :disabled="filterTransactionsForm.processing"

            @click="showAll"
          >
            Show All
          </button>
        </div>
      </div>

      <div v-if="transactions.length > 0" class="place-self-end overflow-x-auto sm:-mx-6 lg:-mx-8">
        <div class="py-2 min-w-full sm:px-6 lg:px-8">
          <div class="m-1 overflow-hidden" >
            Results per page
            <button class="mx-2" @click="perPage = 5">5</button>
            <button class="mx-2" @click="perPage = 10">10</button>
            <button class="mx-2" @click="perPage = 20">20</button>
            <button class="mx-2" @click="perPage = 50">50</button>
          </div>

          <div class="m-1 overflow-hidden" style="text-align: right">
            <div>
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
          </div>
        </div>
      </div>
    </div>

    <div v-if="transactions.length > 0" class="flex flex-col">
      <div class="overflow-x-auto sm:-mx-6 lg:-mx-8">
        <div class="focus:ring-4 focus:outline-none font-medium rounded-lg tpy-2 inline-block min-w-full sm:px-6 lg:px-8">
          <div class="overflow-hidden">
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
                  <th>Yeet button</th>
                </tr>
              </thead>

              <tbody>
                <tr v-for="item in paginatedItems" :key="item.uuid" :class="{ 'bg-gray-100':true, 'border-b': true, 'credit':item.asset, 'debit': ! item.asset }">
                  <td v-for="{ key } in fields" :key="key" class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                    <slot :name="`cell(${key})`" :value="item[key]" :item="item">
                      {{ item[key] }}
                    </slot>
                  </td>

                  <td class="text-center">
                    <button class="text-center cursor-pointer text-sm text-red-500" @click="confirmTransactionDeletion(item)">
                      <span class="text-center">❌</span>
                    </button>
                    <ConfirmationModal :show="transBeingDeleted != null" @close="transBeingDeleted = null">
                        <template #title>
                            Delete Transaction
                        </template>

                        <template #content>
                            You sure you wanna delete this mofo?
                        </template>

                        <template #footer>
                          <SecondaryButton @click="transBeingDeleted = null">
                              Cancel
                          </SecondaryButton>

                          <DangerButton
                            class="ml-3"
                            :class="{ 'opacity-25': deleteTransactionForm.processing }"
                            :disabled="deleteTransactionForm.processing"
                            @click="deleteTransaction"
                          >
                                Delete
                          </DangerButton>
                        </template>
                    </ConfirmationModal>
                  </td>
                </tr>

              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <div v-else>
      <p>No transactions found in the given date range</p>
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

