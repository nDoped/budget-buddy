<script setup>
  import { onMounted, ref } from 'vue';

  defineProps({
    modelValue: {
      type: String,
      default: () => ''
    }
  });

  defineEmits(['update:modelValue']);

  const input = ref(null);

  onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
      input.value.focus();
    }
  });

  defineExpose({ focus: () => input.value.focus() });
</script>

<template>
  <div class="flex flex-col sm:flex-row">
    <div class="none">
      <input
        ref="input"
        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
        :value="modelValue"
        type="date"
        @input="$emit('update:modelValue', $event.target.value)"
      >
    </div>
    <div class="justify-start">
      <button
        class="text-rose-700 ml-2 focus:ring-4 focus:outline-none font-small rounded-sm sm:w-auto px-1 py-1"
        @click="$emit('update:modelValue', '')"
        type="button"
      >
        X
      </button>
    </div>
  </div>
</template>


