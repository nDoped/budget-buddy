<script setup>
import { useForm } from '@inertiajs/vue3'
import InputDate from '@/Components/InputDate.vue';
import InputLabel from '@/Components/InputLabel.vue';
import { ref, onMounted, watch } from 'vue';

const transStart = ref(props.start);
const transEnd = ref(props.end);
const filterData = ref({});
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

watch([ () => props.start, () => props.end ], (args) => {
  transStart.value = args[0];
  transEnd.value = args[1];
});
watch([ () => transStart.value, () => transEnd.value ], ([newStart, newEnd]) => {
  if (newStart && newEnd) {
    filterData.value = {
      start: newStart,
      end: newEnd
    };

  } else if (newStart) {
    filterData.value = {
      start: newStart,
      end: null
    };

  } else if (newEnd) {
    filterData.value = {
      start: null,
      end: newEnd
    };

  } else if (props.includeShowAll) {
    filterData.value = {
      show_all: true
    };

  } else {
    filterData.value = {
      start: null,
      end: null
    };
  }
});

</script>

<template>
  <div class="m-2">
    <InputLabel for="transaction_start_date" value="Start Date" class="text-white"/>
    <InputDate id="transactions_start_date" v-model="transStart" />

    <span class="mt-1">
      <InputLabel for="transaction_end_date" value="End Date" class="text-white"/>
      <InputDate id="transactions_start_date" v-model="transEnd" />
    </span>
  </div>

  <div class="m-1">
    <button
      :class="{ 'opacity-25': processing }"
      :disabled="processing"
      class="text-white bg-gray-600  focus:ring-4 focus:outline-none font-medium rounded-lg text-sm w-full sm:w-auto px-5 py-2.5 text-center "
      @click="filter"
    >
      <slot />
    </button>
  </div>
</template>
