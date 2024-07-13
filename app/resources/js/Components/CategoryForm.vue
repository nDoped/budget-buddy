<script setup>
  import {
    ref,
  } from 'vue';
  import { useForm } from '@inertiajs/vue3'
  import { toast } from 'vue3-toastify';
  import CategoryInputs from '@/Components/CategoryInputs.vue';
  import PrimaryButton from '@/Components/PrimaryButton.vue';

  const success = () => {
    toast.success('Category Created!');
    inputIncrementer.value++;
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
    hex_color: '#000000',
    category_type: '',
  });
  const updateInputs = ({ name, hex_color, type }) => {
    form.name = name;
    form.hex_color = hex_color;
    form.category_type = type;
  };
  const inputIncrementer = ref(0);
  function submit() {
    /* global route */
    form.post(route('categories.store'), {
      preserveScroll: true,
      onSuccess: success,
      onError: () =>  {
        console.error(form.errors.name)
      }
    });
  }
</script>

<template>
  <form
    @submit.prevent="submit"
  >
    <CategoryInputs
      :errors="form.errors"
      :key="inputIncrementer"
      :category-types="categoryTypes"
      @field-update="updateInputs"
    />

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
