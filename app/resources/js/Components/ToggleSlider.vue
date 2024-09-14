<script setup lang=ts>
  defineEmits([ 'update:modelValue' ]);
  const model = defineModel({
    type: Boolean,
    default: false
  });
  defineProps({
    label: {
      type: String,
      default: 'Turn it on'
    },
  });
</script>

<template>
  <div class="bg-slate-500 inline-block rounded">
    <label class="relative flex justify-start items-center p-2 text-l">
      <input
        type="checkbox"
        v-model="model"
        class="absolute left-1/2 -translate-x-1/2 opacity-0 w-full h-full peer appearance-none rounded-md"
      >
      <span
        class="w-16 h-5 m-2 flex items-center flex-shrink-0 ml-4 p-1 bg-gray-300 rounded-full duration-300 ease-in-out peer-checked:bg-green-400 after:w-8 after:h-3 after:bg-white after:rounded-full after:shadow-md after:duration-300 peer-checked:after:translate-x-6"
      />
      {{ label }}
    </label>
  </div>
</template>
