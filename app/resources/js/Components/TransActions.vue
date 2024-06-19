<script setup>
  import { inject, ref, onMounted, watch } from 'vue';
  import { useForm } from '@inertiajs/vue3'
  import TransactionsForm from '@/Components/TransactionsForm.vue';
  import SearchInput from '@/Components/SearchInput.vue';
  import ElasticFrame from '@/Components/ElasticFrame.vue';
  import TransactionEditForm from '@/Components/TransactionEditForm.vue';
  import DateFilter from '@/Components/DateFilter.vue';
  import ExpandableTable from '@/Components/ExpandableTable.vue';

  const formatter = inject('formatter');

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
  ]);

  onMounted(() => {
    transStart.value = props.start;
    transEnd.value = props.end;
  });

  const hideTr = (hiddenTrRefs, i) => {
    hiddenTrRefs[i].classList.add("hidden");
  };

  const fetchCatString = (cats) => {
    let ret = '';
    let catCnt = cats.length;
    if (catCnt === 1) {
      ret += `<span style='border-bottom: solid ${cats[0].color}'>`;
      ret += `${cats[0].name}`;
      ret += '</span>';

    } else {
      cats.forEach((c) => {
        ret += `<span style='border-bottom: solid ${c.color}'>`;
        ret += ` ${c.name} : ${c.percent}%`;
        ret += '</span><br/>';
      });
    }
    return ret;
  };

  const colorText = (key) => {
    let test = fields.value.find(field => field.key === key );
    if (test.color_text) {
      return true;
    }
    return false;
  };
  const searchText = ref('');
  const filteredTransactions = ref(props.transactions);

  const clearSearchResults = () => {
    filteredTransactions.value = props.transactions;
    resetSearchMatchTextValues();
  };
  const resetSearchMatchTextValues = () => {
    filteredTransactions.value.forEach((t) => {
      t.accountSearchMatchText = t.account;
      t.noteSearchMatchText = t.note;
      t.amountSearchMatchText = formatter.format(t.amount);
      // use this member for search matches
      t.amountFormated = formatter.format(t.amount);
    });
  };
  onMounted(() => {
    resetSearchMatchTextValues();
    watch(() => props.transactions, () => {
      searchText.value = '';
      clearSearchResults();
    });
  });

  const buildMarkString = (query, value) => {
    let index = value.toUpperCase().indexOf(query.toUpperCase());
    let ret = value.substring(0, index)
      + "<mark>"
      + value.substring(index, index + query.length)
      + "</mark>" + value.substring(index + query.length);
    return ret;
  };
  watch(searchText, (query) => {
    applySearchFilter(query);
  });
  const applySearchFilter = (query) => {
    if (! query) {
      clearSearchResults();
      return;
    }
    let searchResults = [];
    props.transactions.forEach((t) => {
      if (t.account.toLowerCase().includes(query.toLowerCase())
        || (t.note && t.note.toLowerCase().includes(query.toLowerCase()))
        || t.amountFormated.includes(query)
      ) {

        if (t.account.toLowerCase().includes(query.toLowerCase())) {
          t.accountSearchMatchText = buildMarkString(query, t.account);
        } else {
          t.accountSearchMatchText = t.account;
        }
        if (t.note && t.note.toLowerCase().includes(query.toLowerCase())) {
          t.noteSearchMatchText = buildMarkString(query, t.note);
        } else {
          t.noteSearchMatchText = t.note;
        }

        if (t.amountFormated.includes(query)) {
          t.amountSearchMatchText = buildMarkString(query, t.amountFormated);
        } else {
          t.amountSearchMatchText = t.amountFormated;
        }

        searchResults.push(t);
      }

      filteredTransactions.value = searchResults;
    });
  };
</script>

<template>
  <div class="p-6 sm:px-20 bg-slate-100 dark:bg-slate-700 border-b border-gray-200">
    <TransactionsForm
      :accounts="accounts"
      :categories="categories"
      :category-types="categoryTypes"
    />

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

      <SearchInput
        v-if="transactions.length > 0"
        class="place-self-end"
        v-model="searchText"
        input-name="search"
        hint="Search Transactions"
        placeholder="Search"
      />
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
            <div v-if="key === 'transaction_date'">
              {{
                new Date(item[key])
                  .toLocaleString('us-en', {
                    timeZone: "utc",
                    weekday: "short",
                    year: "numeric",
                    month: "numeric",
                    day: "numeric",
                  })
              }}
            </div>

            <div v-else-if="key === 'parent_transaction_date' && value">
              {{
                item.parent_id + ' - ' + new Date(item[key])
                  .toLocaleString('us-en', {
                    timeZone: "utc",
                    year: "numeric",
                    month: "numeric",
                    day: "numeric",
                  })
              }}
            </div>

            <div
              v-else-if="key === 'categories'"
              class="w-full"
              v-html="fetchCatString(value)"
            />

            <div
              v-else
              v-html="value"
            />
          </div>
        </template>

        <template #hidden_row="{hidden_tr_refs, item, i}">
          <TransactionEditForm
            :accounts="accounts"
            :transaction="item"
            :categories="categories"
            :category-types="categoryTypes"
            @cancel="hideTr(hidden_tr_refs, i)"
            @success="hideTr(hidden_tr_refs, i)"
          />
        </template>
      </ExpandableTable>

      <div v-else>
        <p>No transactions found in the given date range</p>
      </div>
    </div>
  </div>
</template>

