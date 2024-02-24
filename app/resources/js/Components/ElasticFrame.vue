<template>
  <component
    :is="props.tag"
    class="grid grid-flow-row content-between gap-5 sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-2"
  >
    <!--
    -->
    <slot />
  </component>
</template>
<script setup>
  const props = defineProps({
    tag: {
      type: String,
      default: "div",
    },
  });
</script>

