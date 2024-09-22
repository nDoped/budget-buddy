<script setup>
  defineProps({
    type: {
      type: String,
      default: 'button',
    },
  });
</script>

<template>
  <button
    :type="type"
    class="
      inline-flex
      items-center
      m-1
      px-4
      py-2
      bg-gray-600
      border
      border-gray-500
      rounded-md
      font-semibold
      text-xs
      text-gray-400
      uppercase
      tracking-widest
      shadow-sm
      hover:text-gray-300
      focus:outline-none
      focus:border-blue-300
      focus:ring
      focus:ring-blue-200
      active:text-gray-800
      active:bg-gray-500
      disabled:opacity-25
      transition
    "
  >
    <slot />
  </button>
</template>
