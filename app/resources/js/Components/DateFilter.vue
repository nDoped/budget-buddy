<script setup>
  import InputDate from '@/Components/InputDate.vue';
  import InputLabel from '@/Components/InputLabel.vue';
  import Checkbox from '@/Components/Checkbox.vue';
  import SecondaryButton from '@/Components/SecondaryButton.vue';
  import {
    ref,
    onMounted,
    computed,
    watch
  } from 'vue';

  onMounted(() => {
    transStart.value = props.start;
    transEnd.value = props.end;
    filterData.value = {
      start: props.start,
      end: props.end
    };
  });

  let props = defineProps({
    start: {
      type: String,
      default: null
    },
    end: {
      type: String,
      default: null
    },
    accounts: {
      type: Array,
      default: () => []
    },
    showTransactionsLink: {
      type: Boolean,
      default: false
    },
    processing: {
      type: Boolean,
      default: false
    },
    includeShowAll: {
      type: Boolean,
      default: true
    },
  });

  const emit = defineEmits(['filter']);
  const filter = () => {
    emit('filter', filterData);
  }

  const transStart = ref(props.start);
  const transEnd = ref(props.end);
  watch([ () => props.start, () => props.end ], (args) => {
    transStart.value = args[0];
    transEnd.value = args[1];
  });

  const useStartDate = ref(false);
  watch(() => useStartDate.value, () => {
    if (useStartDate.value) {
      transEnd.value = transStart.value;
    }
  });
  watch(() => transStart.value, () => {
    if (useStartDate.value) {
      transEnd.value = transStart.value;
    }
  });

  const filterData = ref({});
  const filterAccounts = ref({});
  watch(() => filterAccounts.value, () => {
    filterData.value.filter_accounts = filterAccounts.value;
  });

  const transactionsUrl = computed(() => {
    let url = 'transactions';
    if (transStart.value && transEnd.value) {
      url += `?start=${props.start}&end=${props.end}`;

    } else if (transStart.value) {
      url += `?start=${props.start}`;

    } else if (transEnd.value) {
      url += `?end=${props.end}`;

    } else if (props.includeShowAll) {
      url += `?show_all=1`;

    } else {
      url += `?use_session_filters=1`;
    }
    return url;
  });

  watch([ () => transStart.value, () => transEnd.value ], ([newStart, newEnd]) => {
    if (newStart && newEnd) {
      filterData.value.start = newStart;
      filterData.value.end = newEnd;
      filterData.value.show_all = null;

    } else if (newStart) {
      filterData.value.start = newStart;
      filterData.value.end = null;
      filterData.value.show_all = null;

    } else if (newEnd) {
      filterData.value.start = null;
      filterData.value.end = newEnd;
      filterData.value.show_all = null;

    } else if (props.includeShowAll) {
      filterData.value.show_all = true;
      filterData.value.start = null;
      filterData.value.end = null;

    } else {
      filterData.value.start = null;
      filterData.value.end = null;
      filterData.value.show_all = null;
    }
  });
</script>

<template>
  <div class="flex flex-col sm:flex-row ">
    <div class="m-2">
      <InputLabel
        for="transaction_start_date"
        value="Start Date"
        class="text-black dark:text-white"
      />
      <InputDate
        id="transactions_start_date"
        v-model="transStart"
      />
    </div>

    <div class="m-2">
      <InputLabel
        for="transaction_end_date"
        value="End Date"
        class="text-black dark:text-white"
      />
      <InputDate
        id="transactions_start_date"
        v-model="transEnd"
      />
      <InputLabel
        for="use-start-date"
        class="mt-2 text-black dark:text-white"
      >
        <span class="pr-2">Use Start Date?</span>
        <Checkbox
          id="use-start-date"
          v-model:checked="useStartDate"
          name="use_start"
        />
      </InputLabel>
    </div>

    <div
      v-if="accounts.length > 0"
      class="m-2"
    >
      <InputLabel
        for="type"
        value="Accounts"
        class="text-black dark:text-white"
      />
      <select
        id="type"
        multiple
        v-model="filterAccounts"
        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
      >
        <option
          v-for="(account, i) in accounts"
          :key="i"
          :value="account.id"
        >
          {{ account.name }}
        </option>
      </select>
    </div>

    <div class="m-2 mt-7">
      <SecondaryButton
        type="button"
        :class="{ 'opacity-25': processing }"
        :disabled="processing"
        @click="filter"
      >
        <slot name="range_button_text">
          Select Range
        </slot>
      </SecondaryButton>

      <div class="mt-2">
        <a
          v-if="showTransactionsLink"
          class="underline"
          :href="transactionsUrl"
        >
          View these transactions
        </a>
      </div>
    </div>
  </div>
</template>
