<script setup>
  import { onMounted, ref } from 'vue';
  const model = defineModel({type: String, default: ''});
  const input = ref(null);
  onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
      input.value.focus();
    }
  });
  defineExpose({ focus: () => input.value.focus() });
</script>

<template>
  <textarea
    ref="input"
    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
    v-model="model"
  >
    <slot />
  </textarea>
</template>
