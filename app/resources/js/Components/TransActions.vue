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
    { key: 'id', label: 'ID', sortable: true, color_text:false },
    { key: 'transaction_date', label: 'Transaction Date', sortable: true, color_text:false },
    { key: 'asset_text', label: 'Credit/Debit', sortable: true, color_text:true },
    { key: 'amount', label: 'Amount', sortable:true, color_text:true, format:true },
    { key: 'account', label: 'Account', sortable:true, color_text:false },
    { key: 'categories', label: 'Categories', sortable:true, color_text:false, stringify:true },
    //{ key: 'account_type', label: 'Account Type', sortable:true, color_text:false },
    { key: 'bank_identifier', label: 'Bank Identifier', sortable:false, color_text:false },
    { key: 'note', label: 'Note', sortable:false, color_text:false },
  ]);

  onMounted(() => {
    transStart.value = props.start;
    transEnd.value = props.end;
  });

  const hideTr = (hiddenTrRefs, i) => {
    console.log('transactions cancelRef() handler', hiddenTrRefs, i);
    hiddenTrRefs[i].classList.add("hidden");
  };

  const formatField = (key) => {
    let test = fields.value.find(field => field.key === key );
    if (test.format) {
      return true;
    }
    return false;
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
  <div class="p-6 sm:px-20 bg-slate-700 border-b border-gray-200">
    <TransactionsForm :accounts="accounts" />

    <div class="flex flex-row">
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
    </div>

    <ExpandableTable
      v-if="transactions.length > 0"
      :items="transactions"
      :fields="fields"
    >
      <template #visible_row="{ item , value, key }">
        <div
          :class="{
            'text-green-400': colorText(key, value, item) && item.asset,
            'text-rose-800': colorText(key, value, item) && ! item.asset,
          }"
          class="font-semibold text-xl"
        >
          <div v-if="formatField(key, value, item)">
            {{ formatter.format(item[key]) }}
          </div>
          <div v-else>
            {{ value }}
          </div>
        </div>
      </template>

      <template #hidden_row="{hidden_tr_refs, item, i}">
        <TransactionEditForm
          :accounts="accounts"
          :transaction="item"
          @cancel="hideTr(hidden_tr_refs, i)"
          @success="hideTr(hidden_tr_refs, i)"
        />
      </template>
    </ExpandableTable>

    <div v-else>
      <p>No transactions found in the given date range</p>
    </div>
  </div>
</template>

