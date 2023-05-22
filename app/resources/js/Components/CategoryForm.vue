<script setup>
  import { useForm } from '@inertiajs/vue3'
  import { toast } from 'vue3-toastify';
  import InputLabel from '@/Components/InputLabel.vue';
  import InputError from '@/Components/InputError.vue';
  import PrimaryButton from '@/Components/PrimaryButton.vue';
  import TextInput from '@/Components/TextInput.vue';

  const success = () => {
    toast.success('Category Created!');
    form.reset();
  };

  defineProps({
    categoryTypes: {
      type: Object,
      default: () => {}
    }
  });

  const form = useForm({
    name: null,
    color: '#000000',
    category_type: '',
  });
  function submit() {
    /* global route */
    form.post(route('categories.store'), {
      preserveScroll: true,
      onSuccess: success,
      onError: (err) =>  {
        console.error(err.message)
      }
    });
  }
</script>

<template>
  <form
    @submit.prevent="submit"
  >
    <div class="flex flex-wrap p-6 bg-slate-500 border-b border-gray-200">
      <div class="m-4">
        <InputLabel
          for="name"
          value="Name"
        />
        <TextInput
          id="name"
          v-model="form.name"
          type="text"
          class="mt-1 block w-full"
          autocomplete="bank_ident"
        />
        <InputError
          :message="form.errors.name"
          class="mt-2"
        />
      </div>

      <div class="m-4">
        <InputLabel
          for="type"
          value="Category Type"
        />
        <select
          id="type"
          v-model="form.category_type"
          class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
        >
          <option
            selected
            value=""
          >
            Select type...
          </option>

          <option
            v-for="(type, i) in categoryTypes"
            :key="i"
            :value="type.id"
          >
            {{ type.name }}
          </option>
        </select>
      </div>

      <div class="m-4">
        <InputLabel
          for="color"
          value="color"
        />
        <input
          type="color"
          v-model="form.color"
        >
        <InputError
          :message="form.errors.color"
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
        Add Category
      </PrimaryButton>
    </div>
  </form>
</template>
