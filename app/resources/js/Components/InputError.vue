<script setup>
  defineProps({
    message: {
      type: String,
      default: () => ''
    }
  });
</script>

<template>
  <div :class="{ 'invisible': ! message }">
    <p class="text-sm text-red-600 dark:text-red-400">
      {{ message }}
    </p>
  </div>
</template>
