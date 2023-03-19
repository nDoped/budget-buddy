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

  const form = useForm({
    name: null,
    color: '#000000',
    category_type: null,
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

      <div class="m-4">
        <InputLabel
          for="primary_income"
          value="Primary Income?"
        />
        <input
          id="primary_income"
          v-model="form.category_type"
          value="primary_income"
          type="radio"
        >
      </div>

      <div class="m-4">
        <InputLabel
          for="secondary_income"
          value="Secondary Income?"
        />
        <input
          id="secondary_income"
          v-model="form.category_type"
          value="secondary_income"
          type="radio"
        >
      </div>

      <div class="m-4">
        <InputLabel
          for="regular_expense"
          value="Regular Expense?"
        />
        <input
          id="regular_expense"
          v-model="form.category_type"
          value="regular_expense"
          type="radio"
        >
      </div>

      <div class="m-4">
        <InputLabel
          for="recurring_expense"
          value="Recurring Expense?"
        />
        <input
          id="recurring_expense"
          v-model="form.category_type"
          value="recurring_expense"
          type="radio"
        >
      </div>

      <div class="m-4">
        <InputLabel
          for="extra_expense"
          value="Extra Expense?"
        />
        <input
          id="extra_expense"
          v-model="form.category_type"
          value="extra_expense"
          type="radio"
        >
      </div>

      <div class="m-4">
        <InputLabel
          for="housing_expense"
          value="Housing?"
        />
        <input
          id="housing_expense"
          v-model="form.category_type"
          value="housing_expense"
          type="radio"
        >
      </div>

      <div class="m-4">
        <InputLabel
          for="utility_expense"
          value="Utility?"
        />
        <input
          id="utility_expense"
          v-model="form.category_type"
          value="utility_expense"
          type="radio"
        >
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
<style>

</style>
