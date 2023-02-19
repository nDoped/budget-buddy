<script setup>
  import { computed, reactive, watch, ref, onMounted, onBeforeUpdate } from 'vue';
  import { Link } from '@inertiajs/vue3';
  import { sort } from 'fast-sort'
  import { useForm } from '@inertiajs/vue3'
  import TransactionsForm from '@/Components/TransactionsForm.vue';
  import TransactionEditForm from '@/Components/TransactionEditForm.vue';
  import DateFilter from '@/Components/DateFilter.vue';
  const sortBy = ref(null);
  const sortDesc = ref(null);

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

  const filterTransactionsForm = useForm({});
  const filterEventHandler = (data) => {
      filterTransactionsForm.get(route('transactions', data.value), {
          preserveScroll: true,
          preserveState: true,
      });
  }

  const fields = ref([
    { key: 'id', label: 'ID', sortable: true, color_text:false },
    { key: 'transaction_date', label: 'Transaction Date', sortable: true, color_text:false },
    { key: 'asset_text', label: 'Credit/Debit', sortable: true, color_text:true },
    { key: 'amount', label: 'Amount', sortable:true, color_text:true },
    { key: 'account', label: 'Account', sortable:true, color_text:false },
    //{ key: 'account_type', label: 'Account Type', sortable:true, color_text:false },
    { key: 'bank_identifier', label: 'Bank Identifier', sortable:false, color_text:false },
    { key: 'note', label: 'Note', sortable:false, color_text:false },
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

  // make sure to reset the refs before each update
  const tableRowRefs = ref([]);
  onBeforeUpdate(() => {
    tableRowRefs.value = []
  });
  const showHideRow = (trans, i) => {
    let hiddenRow = document.getElementById(`hidden_row_${i}`);
    let visibleRow = tableRowRefs.value[i];
    let nextVisibleRow = tableRowRefs.value[i + 1];
    let hiddenRowClasses = [
      "open_row",
    ];
    if (hiddenRow.classList.contains("hidden")) {
      document.getElementById(`hidden_row_${i}`).classList.remove("hidden");
      visibleRow.classList.remove("border-b");
      if (nextVisibleRow) {
        nextVisibleRow.classList.add("border-t");
      }
      /**
       * Force a browser re-paint so the browser will realize the
       * element is no longer `hidden` and allow transitions.
       */
      const reflow = hiddenRow.offsetHeight;

      hiddenRow.classList.add(...hiddenRowClasses);
    } else {

      const reflow = hiddenRow.offsetHeight;
      hiddenRow.classList.add("hidden");
      visibleRow.classList.add("border-b");
      if (nextVisibleRow) {
        nextVisibleRow.classList.remove("border-t");
      }
    }
  };

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
            <table class="min-w-full table-auto">
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
                <template v-for="(item, i) in paginatedItems" :key="i">
                  <tr
                    @click="showHideRow(item, i)"
                    :ref="(el) => { tableRowRefs.push(el) }"
                    class="hover:opacity-80 focus:bg-slate-400"
                    :class="{
                      'bg-slate-800':true,
                      'border-b': true,
                    }"
                  >
                    <td
                      v-for="{ key, label, sortable, color_text } in fields"
                      :key="key"
                      class="text-center px-2 py-1 text-md font-medium"
                      :class="{
                        'text-green-400': color_text && item.asset,
                        'text-rose-800': color_text && ! item.asset,
                        'text-gray-400': ! color_text
                      }"
                    >
                      <slot :name="`cell(${key})`" :value="item[key]" :item="item">
                        {{ item[key] }}
                      </slot>
                    </td>
                  </tr>

                  <tr :id="`hidden_row_${i}`" class="hidden">
                    <td colspan="7" class="w-full">
                      <TransactionEditForm
                        :accounts="accounts"
                        :transaction="item"
                        @success="showHideRow(item, i)"
                      />
                    </td>
                  </tr>
                </template>
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
  th.sortable {
    cursor: pointer;
  }

  .text {
    overflow: hidden;
    max-height: 0;
    transition: max-height 0.5s cubic-bezier(0, 1, 0, 1);
  }

  .open_row {
    max-height: 1000px;
    transition: max-height 1s ease-in-out;
  }
</style>

