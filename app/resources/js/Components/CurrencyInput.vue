<script setup>
  import {
    onMounted,
    watch,
    ref
  } from 'vue';
  import { forceMonetaryInput } from '@/lib.js';
  import InputLabel from '@/Components/InputLabel.vue';
  import InputError from '@/Components/InputError.vue';
  const input = ref(null);

  const model = defineModel({type: String, default: ''});
  const value = ref(parseFloat(model.value));
  watch(value, () => {
    model.value = String(value.value);
  });
  watch(model, () => {
    value.value = (model.value) ? parseFloat(model.value) : "";
  });

  defineProps({
    label: {
      type: String,
      default: 'Amount'
    },
    errorMessage: {
      type: String,
      default: ''
    },
    inputId: {
      type: String,
      required: true
    },
    extraClasses: {
      type: String,
      default: ''
    }
  });
  onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
      input.value.focus();
    }
  });
  defineExpose({ focus: () => input.value.focus() });
</script>

<template>
  <div class="w-48">
    <InputLabel
      :for="inputId"
      :value="label"
    />
    <div class="relative max-w-xxxs text-gray-900 dark:text-white">
      <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
        <span class="text-surface-500 sm-:text-sm">$</span>
      </div>
      <input
        :id="inputId"
        ref="input"
        v-model="value"
        step="0.01"
        type="number"
        @keypress="forceMonetaryInput($event)"
        class="w-48 !pl-7 pb-2.5 pt-2.5 bg-gray-50 border border-gray-300 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:focus:ring-blue-500 dark:focus:border-blue-500"
        :class="extraClasses"
      >
      <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
        <span class="text-surface-500 sm:text-sm">USD</span>
      </div>
    </div>
    <InputError
      :message="errorMessage"
      class="mt-2"
    />
  </div>
</template>

<style>
/* Hide the spin buttons in WebKit browsers */
input::-webkit-outer-spin-button,
input::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}

/* Hide spin buttons in Firefox */
input[type="number"] {
  -moz-appearance: textfield;
}
</style>
