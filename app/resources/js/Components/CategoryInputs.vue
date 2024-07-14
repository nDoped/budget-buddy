<script setup>
  import InputLabel from '@/Components/InputLabel.vue';
  import TextInput from '@/Components/TextInput.vue';
  import InputError from '@/Components/InputError.vue';
  import Checkbox from '@/Components/Checkbox.vue';
  import {
    ref,
    onMounted,
    watch
  } from 'vue';
  const emit = defineEmits([ 'fieldUpdate' ]);
  const props = defineProps({
    name: {
      type: String,
      default: ''
    },
    type: {
      type: Number,
      default: null
    },
    color: {
      type: String,
      default: null
    },
    active: {
      type: Boolean,
      default: false
    },
    categoryTypes: {
      type: Array,
      default: () => []
    },
    errors: {
      type: Object,
      default: () => {return {}}
    },
    includeActiveInput: {
      type: Boolean,
      default: false
    }
  });

  const catName = ref(props.name);
  const catColor = ref((props.color) ? props.color : '#000000');
  const catType = ref(props.type);
  const catActive = ref(props.active);
  watch(
    [catName, catColor, catType, catActive ],
    ([newName, newColor, newType, newActive ]) => {
      emit('fieldUpdate', { name: newName, hex_color: newColor, type: newType, active: newActive});
    }
  );
  const uuid = crypto.randomUUID();
  const getUuid = (el) => {
    return `${el}-${uuid}`;
  };
  const nameEl = ref(null);
  onMounted(() => {
    nameEl.value.focus();
  });
</script>

<template>
  <div class="flex flex-wrap p-6 bg-slate-500 border-b border-gray-200">
    <div class="m-4">
      <InputLabel
        :for="getUuid('cat-name')"
        value="Name"
      />
      <TextInput
        :id="getUuid('cat-name')"
        ref="nameEl"
        v-model="catName"
        type="text"
        class="mt-1 block w-full"
      />
      <InputError
        :message="errors.name"
        class="mt-2"
      />

      <InputError
        v-if="errors.categories"
        :message="errors.categories[0].name"
        class="mt-2"
      />
    </div>

    <div class="m-4">
      <InputLabel
        :for="getUuid('cat-type')"
        value="Category Type"
      />
      <select
        :id="getUuid('cat-type')"
        v-model="catType"
        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
      >
        <option value="">
          Select type...
        </option>

        <option
          v-for="(ctype, i) in categoryTypes"
          :key="i"
          :value="ctype.id"
        >
          {{ ctype.name }}
        </option>
      </select>
    </div>

    <div class="m-4">
      <InputLabel
        :for="getUuid('cat-color')"
        value="color"
      />
      <input
        :id="getUuid('cat-color')"
        type="color"
        v-model="catColor"
      >
      <InputError
        :message="errors.color"
        class="mt-2"
      />
    </div>

    <div
      v-if="includeActiveInput"
      class="m-4"
    >
      <InputLabel
        :for="getUuid('cat-active')"
        value="Active"
      />
      <Checkbox
        v-model:checked="catActive"
        name="active"
      />
    </div>
  </div>
</template>
