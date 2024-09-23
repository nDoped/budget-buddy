<script setup>
  import {
    ref,
    nextTick
  } from 'vue';
  import DangerButton from '@/Components/DangerButton.vue';
  import SecondaryButton from '@/Components/SecondaryButton.vue';
  import InputLabel from '@/Components/InputLabel.vue';

  const emit = defineEmits(['delete']);
  const model = defineModel({
    type: Object,
    default: () => {
      return {
        name: '',
      };
    }
  });
  const edit = ref(false);
  const inputRef = ref(null);
  const editMode = async () => {
    edit.value = true;
    await nextTick(() => {
      inputRef.value.focus();
      inputRef.value.select();
    });
  };
  const uuid = crypto.randomUUID();
  const getUuid = (el, i = 0) => {
    return `${el}-${i}-${uuid}`;
  };
  const viewImage = () => {
    /* global axios route */
    return axios.get(
      route('images.data', { id: model.value.id })
    ).then(response => {
      let link = document.createElement("a");
      link.download = model.value.name;
      link.href = response.data.base64
      link.click();
    })
  };
</script>

<template>
  <div class="w-32 bg-cover bg-no-repeat bg-center">
    <div class="flex flex-col mr-2">
      <InputLabel
        :for="getUuid('name')"
        value="Name"
      />
      <p
        class="group truncate hover:bg-slate-300 hover:rounded"
        :aria-label="model.name"
      >
        <span
          class="z-20 p-2 group-hover:overflow-visible group-hover:whitespace-normal group-hover:w-auto"
          @click="editMode"
        >
          <input
            v-if="edit"
            v-model="model.name"
            ref="inputRef"
            :id="getUuid('name')"
            label="File Name"
            class="appearance-none bg-transparent border-none h-0 text-gray-700"
            @keyup.enter="edit=false"
            @keydown.enter.prevent
          >

          <span
            v-else
            class="text-gray-700"
          >
            {{ model.name }}
          </span>
        </span>
      </p>
      <img
        v-if="model.base64"
        :src="model.base64"
        class="mb-2 rounded-lg transition-transform duration-200 hover:scale-105"
      >
      <!-- it's an existing image -->
      <div
        v-else
        class="flex flex-col justify-center"
      >
        <img
          :src="model.thumbnail"
          class="mb-2 rounded-lg transition-transform duration-200 hover:scale-105"
        >
        <SecondaryButton
          class="max-h-1"
          type="button"
          @click="viewImage"
        >
          Download
        </SecondaryButton>
      </div>
      <DangerButton
        class="max-h-1"
        type="button"
        @click="emit('delete')"
      >
        Delete
      </DangerButton>
    </div>
  </div>
</template>
