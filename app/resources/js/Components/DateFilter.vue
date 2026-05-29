<script setup>
  import InputDate from '@/Components/InputDate.vue';
  import InputLabel from '@/Components/InputLabel.vue';
  import Checkbox from '@/Components/Checkbox.vue';
  import SecondaryButton from '@/Components/SecondaryButton.vue';
  import {
    ref,
    onMounted,
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
    processing: {
      type: Boolean,
      default: false
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
  watch(transStart, () => {
    if (useStartDate.value) {
      transEnd.value = transStart.value;
    }
  });

  const filterData = ref({});
  const filterAccounts = ref([]);
  watch(filterAccounts, () => {
    filterData.value.filter_accounts = filterAccounts.value;
  });

  const pad = (n) => String(n).padStart(2, '0');
  const calcMonthRange = (preset) => {
    const now = new Date();
    let year = now.getFullYear();
    let month = now.getMonth();

    if (preset === 'last') month -= 1;
    else if (preset === 'next') month += 1;

    year += Math.floor(month / 12);
    month = ((month % 12) + 12) % 12;

    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);

    const fmt = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;

    return { start: fmt(firstDay), end: fmt(lastDay) };
  };
  const matchMonthPreset = (start, end) => {
    if (!start || !end) return null;
    for (const preset of ['last', 'current', 'next']) {
      const range = calcMonthRange(preset);
      if (start === range.start && end === range.end) {
        return preset;
      }
    }
    return null;
  };

  const monthPreset = ref(null);
  watch(monthPreset, (preset) => {
    if (!preset) return;
    const range = calcMonthRange(preset);
    transStart.value = range.start;
    transEnd.value = range.end;
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

    } else {
      filterData.value.show_all = true;
      filterData.value.start = null;
      filterData.value.end = null;
    }

    monthPreset.value = matchMonthPreset(newStart, newEnd);
  });
</script>

<template>
  <div class="flex flex-col sm:flex-row flex-wrap ">
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
        id="transactions_end_date"
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
      class="m-2 min-w-48"
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

    <div class="m-2">
      <InputLabel
        value="Quick Month"
        class="text-black dark:text-white"
      />
      <div class="flex flex-col gap-1 mt-1">
        <label class="flex items-center gap-1 text-black dark:text-white text-sm">
          <input
            type="radio"
            v-model="monthPreset"
            value="last"
            class="text-indigo-600"
          />
          Last Month
        </label>
        <label class="flex items-center gap-1 text-black dark:text-white text-sm">
          <input
            type="radio"
            v-model="monthPreset"
            value="current"
            class="text-indigo-600"
          />
          Current Month
        </label>
        <label class="flex items-center gap-1 text-black dark:text-white text-sm">
          <input
            type="radio"
            v-model="monthPreset"
            value="next"
            class="text-indigo-600"
          />
          Next Month
        </label>
      </div>
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

    </div>
  </div>
</template>
