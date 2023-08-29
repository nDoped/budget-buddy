<script setup>
  import { inject, ref, onMounted } from 'vue';
  import { useForm } from '@inertiajs/vue3'
  import TransactionsForm from '@/Components/TransactionsForm.vue';
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
    { key: 'amount', label: 'Amount', sortable:true, color_text:true, searchable:true },
    { key: 'account', label: 'Account', sortable:true, searchable: true, color_text:false },
    { key: 'categories', label: 'Categories', sortable:true, searchable: true, color_text:false },
    { key: 'note', label: 'Note', sortable:true, searchable:true, color_text:false },
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
</script>

<template>
  <div class="p-6 sm:px-20 bg-slate-100 dark:bg-slate-700 border-b border-gray-200">
    <TransactionsForm
      :accounts="accounts"
      :categories="categories"
    />

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

    <div
      v-if="transactions.length > 0"
      class="mt-4 overflow-x-auto"
    >
      <ExpandableTable
        :items="transactions"
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
            <div v-if="key === 'amount'">
              {{ formatter.format(item[key]) }}
            </div>

            <div v-else-if="key === 'transaction_date'">
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

            <div v-else>
              {{ value }}
            </div>
          </div>
        </template>

        <template #hidden_row="{hidden_tr_refs, item, i}">
          <TransactionEditForm
            :accounts="accounts"
            :transaction="item"
            :categories="categories"
            @cancel="hideTr(hidden_tr_refs, i)"
            @success="hideTr(hidden_tr_refs, i)"
          />
        </template>
      </ExpandableTable>
    </div>

    <div v-else>
      <p>No transactions found in the given date range</p>
    </div>
  </div>
</template>

