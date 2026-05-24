<script setup>
  import { inject, ref, nextTick, onMounted, onUnmounted, watch } from 'vue';
  import { useForm } from '@inertiajs/vue3'
  import ElasticFrame from '@/Components/ElasticFrame.vue';
  import TransactionEditForm from '@/Components/TransactionEditForm.vue';
  import DateFilter from '@/Components/DateFilter.vue';
  import ExpandableTable from '@/Components/ExpandableTable.vue';

  const formatter = inject('formatter');
  const dateFormatter = inject('dateFormatter');

  let props = defineProps({
    transactions: {
      type: Array,
      default: () => []
    },
    categories: {
      type: Array,
      default: () => []
    },
    categoryTypes: {
      type: Array,
      default: () => []
    },
    accounts: {
      type: Array,
      default: () => []
    },
    start: {
      type: String,
      default: () => ''
    },
    end: {
      type: String,
      default: () => ''
    }
  });
  const transStart = ref(props.start);
  const transEnd = ref(props.end);

  const filterTransactionsForm = useForm({});
  const filterEventHandler = (data) => {
    /* global route */
    filterTransactionsForm.get(route('transactions', data.value), {
      preserveScroll: true,
      preserveState: true,
    });
  }

  const fields = ref([
    { key: 'id', label: 'ID', sortable: true, searchable: true, color_text:false },
    { key: 'transaction_date', label: 'Transaction Date', sortable: true, searchable: true, color_text:false },
    { key: 'asset_text', label: 'Credit/Debit', sortable: true, color_text:true },
    { key: 'amountSearchMatchText', label: 'Amount', sortable:true, color_text:true, searchable:true, sortColumn: 'amount' },
    { key: 'accountSearchMatchText', label: 'Account', sortable:true, searchable: true, color_text:false },
    { key: 'categories', label: 'Categories', sortable:true, searchable: true, color_text:false },
    { key: 'noteSearchMatchText', label: 'Note', sortable:true, searchable:true, color_text:false },
    { key: 'parent_transaction_date', label: 'Parent Transaction', sortable:true, searchable:true, color_text:false },
    { key: 'created_at', label: 'Created', sortable: true, searchable: true, color_text:false },
  ]);

  onMounted(() => {
    transStart.value = props.start;
    transEnd.value = props.end;
  });

  const colorText = (key) => {
    let test = fields.value.find(field => field.key === key );
    if (test.color_text) {
      return true;
    }
    return false;
  };
  const searchText = ref('');
  const debouncedSearchText = ref('');
  let searchTimer;

  onUnmounted(() => clearTimeout(searchTimer));
  const filteredTransactions = ref(props.transactions);

  let matchedTransactions = [];

  const clearSearchResults = () => {
    filteredTransactions.value = props.transactions;
    resetSearchMatchTextValues();
  };
  const resetSearchMatchTextValues = () => {
    const list = matchedTransactions.length ? matchedTransactions : props.transactions;
    list.forEach((t) => {
      t.accountSearchMatchText = t.account;
      t.noteSearchMatchText = t.note;
      t.amountSearchMatchText = t.amountFormated;
      t.categorySearchMatchText = t._catText;
    });
    matchedTransactions = [];
  };
  const initFormattedValues = () => {
    props.transactions.forEach((t) => {
      t.amountFormated = formatter.format(t.amount);
      t.accountSearchMatchText = t.account;
      t.noteSearchMatchText = t.note;
      t.amountSearchMatchText = t.amountFormated;
      t._catText = fetchCatString(t);
      t.categorySearchMatchText = t._catText;
    });
  };
  onMounted(() => {
    initFormattedValues();
    nextTick(() => {
      const input = document.getElementById('transactions-search');
      if (input && input.value) {
        searchText.value = input.value;
      }
    });
    watch(() => props.transactions, () => {
      initFormattedValues();
      if (searchText.value) {
        debouncedSearchText.value = '';
        nextTick(() => {
          debouncedSearchText.value = searchText.value;
        });
      } else {
        clearSearchResults();
      }
    });
  });

  const queryCategoryMatch = (transaction, query) => {
    if (! transaction.categories || ! query) {
      return false;
    }
    return transaction.categories.some((c) => {
      return c.cat_data.name.toLowerCase().includes(query.toLowerCase());
    });
  };
  const fetchCatString = (transaction, query) => {
    let cats = transaction.categories;
    let ret = '';
    if (! cats) {
      return ret;
    }
    let catCnt = cats.length;
    if (catCnt === 1) {
      ret += `<span style='border-bottom: solid ${cats[0].cat_data.hex_color}'>`;
      if (query) {
        ret += buildMarkString(query, cats[0].cat_data.name);
      } else {
        ret += `${cats[0].cat_data.name}`;
      }
      ret += '</span>';

    } else {
      cats.forEach((c) => {
        ret += `<span style='border-bottom: solid ${c.cat_data.hex_color}'>`;
        if (query) {
          ret += buildMarkString(query, c.cat_data.name);
        } else {
          ret += `${c.cat_data.name}`;
        }
        ret += ` : ${c.percent}%`;

        ret += '</span><br/>';
      });
    }
    return ret;
  };

  const buildMarkString = (query, value) => {
    let index = value.toUpperCase().indexOf(query.toUpperCase());
    if (index === -1) {
      return value;
    }
    let ret = value.substring(0, index)
      + "<mark>"
      + value.substring(index, index + query.length)
      + "</mark>" + value.substring(index + query.length);
    return ret;
  };
  watch(searchText, (val) => {
    clearTimeout(searchTimer);
    if (! val) {
      debouncedSearchText.value = '';
      return;
    }
    searchTimer = setTimeout(() => {
      debouncedSearchText.value = val;
    }, 200);
  });
  watch(debouncedSearchText, (query) => {
    applySearchFilter(query);
    nextTick(() => document.getElementById('transactions-search')?.focus());
  });
  const applySearchFilter = (query) => {
    if (! query) {
      clearSearchResults();
      return;
    }
    let searchResults = [];
    matchedTransactions = [];
    const q = query.toLowerCase();
    props.transactions.forEach((t) => {
      if (t.account.toLowerCase().includes(q)
        || (t.note && t.note.toLowerCase().includes(q))
        || (t.amountFormated && t.amountFormated.includes(query))
        || formatDate(t.transaction_date).includes(query)
        || queryCategoryMatch(t, query)
      ) {

        if (t.account.toLowerCase().includes(q)) {
          t.accountSearchMatchText = buildMarkString(query, t.account);
        } else {
          t.accountSearchMatchText = t.account;
        }
        if (t.note && t.note.toLowerCase().includes(q)) {
          t.noteSearchMatchText = buildMarkString(query, t.note);
        } else {
          t.noteSearchMatchText = t.note;
        }

        if (t.amountFormated && t.amountFormated.includes(query)) {
          t.amountSearchMatchText = buildMarkString(query, t.amountFormated);
        } else {
          t.amountSearchMatchText = t.amountFormated;
        }

        if (queryCategoryMatch(t, query)) {
          t.categorySearchMatchText = fetchCatString(t, query);
        } else {
          t.categorySearchMatchText = fetchCatString(t);
        }

        matchedTransactions.push(t);
        searchResults.push(t);
      }
    });
    filteredTransactions.value = searchResults;
  };
  const formatDate = (isoDate) => {
    return dateFormatter.format(new Date(isoDate));
  };
  const formatParentTransactionDate = (item, key) => {
    let ret = item.parent_id + ' - ' + formatDate(item[key]);
    return ret;
  };
  const getLastTransactionLabel = (item) => {
    let ret = '';
    if (item.is_last_child) {
      ret += 'Last Transaction in Series';
    }
    return ret;
  };
</script>

<template>
  <div class="p-6 sm:px-20 bg-slate-100 dark:bg-slate-700 border-b border-gray-200">
    <ElasticFrame>
      <DateFilter
        :start="transStart"
        :end="transEnd"
        :processing="filterTransactionsForm.processing"
        :accounts="accounts"
        @filter="filterEventHandler"
      >
        <template #range_button_text>
          Filter
        </template>
      </DateFilter>

      <label
        v-if="transactions.length > 0"
        class="place-self-end"
      >
        <div class="relative mt-1">
          <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
            <svg class="h-5 w-5 text-surface-400 dark:text-surface-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
            </svg>
          </div>
          <input
            id="transactions-search"
            type="search"
            class="pl-10 pr-3 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
            placeholder="Search"
            :value="searchText"
            @input="searchText = $event.target.value"
          >
        </div>
        <p class="ls5-form-hint mt-1">Search Transactions</p>
      </label>
    </ElasticFrame>

    <div class="mt-4 overflow-x-auto">
      <ExpandableTable
        v-if="transactions.length > 0"
        :items="filteredTransactions"
        :fields="fields"
      >
        <template #visible_row="{ item , value, key }">
          <div
            :class="{
              'text-green-800 dark:text-green-400': colorText(key, value, item) && item.asset,
              'text-red-800 dark:text-red-400': colorText(key, value, item) && ! item.asset,
            }"
            class="font-medium text-sm"
          >
            <div v-if="key === 'transaction_date' || key === 'created_at'">
              {{ formatDate(item[key]) }}
            </div>

            <div v-else-if="key === 'parent_transaction_date' && value">
              {{
                formatParentTransactionDate(item, key)
              }}
              <br>
              {{
                getLastTransactionLabel(item)
              }}
            </div>

            <div
              v-else-if="key === 'categories'"
              class="w-full"
              v-html="item['categorySearchMatchText']"
            />

            <div
              v-else-if="key === 'accountSearchMatchText' && !item.account_active"
              v-html="value + ' (inactive)'"
            />
            <div
              v-else
              v-html="value"
            />
          </div>
        </template>

        <template #hidden_row="{collapse, item}">
          <TransactionEditForm
            :accounts="accounts"
            :transaction="item"
            :categories="categories"
            :category-types="categoryTypes"
            @cancel="collapse"
            @success="collapse"
          />
        </template>
      </ExpandableTable>

      <div v-else>
        <p>No transactions found in the given date range</p>
      </div>
    </div>
  </div>
</template>
