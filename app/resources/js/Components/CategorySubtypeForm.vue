<script setup>
  import { useForm } from '@inertiajs/vue3'
  import { randomUUID } from '@/lib.js';
  import { toast } from 'vue3-toastify';
  import InputLabel from '@/Components/InputLabel.vue';
  import InputError from '@/Components/InputError.vue';
  import PrimaryButton from '@/Components/PrimaryButton.vue';
  import TextInput from '@/Components/TextInput.vue';

  defineProps({
    categoryTypes: {
      type: Array,
      default: () => []
    }
  });

  const success = () => {
    toast.success('Category Subtype Created!');
    form.reset();
  };

  const form = useForm({
    name: null,
    category_type_id: '',
  });
  function submit() {
    form.post(route('category_subtype.store'), {
      preserveScroll: true,
      onSuccess: success,
      onError: (err) => {
        console.error(err)
      }
    });
  }

  const uuid = randomUUID();
  const getUuid = (el) => {
    return `${el}-${uuid}`;
  };
</script>

<template>
  <form @submit.prevent="submit">
    <div class="flex flex-wrap p-6 bg-slate-500 border-b border-gray-200">
      <div class="m-4">
        <InputLabel
          :for="getUuid('subtype-name')"
          value="Name"
        />
        <TextInput
          :id="getUuid('subtype-name')"
          v-model="form.name"
          type="text"
          class="mt-1 block w-full"
          autocomplete="name"
        />
        <InputError
          :message="form.errors.name"
          class="mt-2"
        />
      </div>

      <div class="m-4">
        <InputLabel
          :for="getUuid('subtype-type')"
          value="Category Type"
        />
        <select
          :id="getUuid('subtype-type')"
          v-model="form.category_type_id"
          class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full max-w-sm p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
        >
          <option value="">
            Select type...
          </option>
          <option
            v-for="(ct, i) in categoryTypes"
            :key="i"
            :value="ct.id"
          >
            {{ ct.name }}
          </option>
        </select>
        <InputError
          :message="form.errors.category_type_id"
          class="mt-2"
        />
      </div>
    </div>

    <div class="flex flex-wrap p-6 bg-slate-500 border-gray-200">
      <PrimaryButton
        class="ml-3"
        type="submit"
        :class="{ 'opacity-25': form.processing }"
        :disabled="form.processing"
      >
        Add Subtype
      </PrimaryButton>
    </div>
  </form>
</template>
