<script setup>
  import {  ref } from 'vue';
  import { useForm } from '@inertiajs/vue3'
  import { toast } from 'vue3-toastify';
  import InputLabel from '@/Components/InputLabel.vue';
  import InputError from '@/Components/InputError.vue';
  import PrimaryButton from '@/Components/PrimaryButton.vue';
  import TextInput from '@/Components/TextInput.vue';
  import Checkbox from '@/Components/Checkbox.vue';

  const success = () => {
    toast.success('Category Created!');
    form.reset();
  };

  const form = useForm({
    name: null,
    color: '#000000',
    extra_expense: true,
    recurring_expense: false,
    housing_expense: false,
    utility_expense: false,
    primary_income: false,
    extra_income: false
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
          for="extra_expense"
          value="Extra Expense?"
        />
        <Checkbox
          id="extra_expense"
          v-model:checked="form.extra_expense"
          name="extra_expense"
        />
        <InputError
          class="mt-2"
          :message="form.errors.extra_expense"
        />
      </div>

      <div class="m-4">
        <InputLabel
          for="recurring_expense"
          value="Recurring Expense?"
        />
        <Checkbox
          id="recurring_expense"
          v-model:checked="form.recurring_expense"
          name="recurring_expense"
        />
        <InputError
          class="mt-2"
          :message="form.errors.recurring_expense"
        />
      </div>

      <div class="m-4">
        <InputLabel
          for="housing_expense"
          value="Housing Expense?"
        />
        <Checkbox
          id="housing_expense"
          v-model:checked="form.housing_expense"
          name="housing_expense"
        />
        <InputError
          class="mt-2"
          :message="form.errors.housing_expense"
        />
      </div>

      <div class="m-4">
        <InputLabel
          for="utility_expense"
          value="Utility?"
        />
        <Checkbox
          id="utility_expense"
          v-model:checked="form.utility_expense"
          name="utility_expense"
        />
        <InputError
          class="mt-2"
          :message="form.errors.utility_expense"
        />
      </div>


      <div class="m-4">
        <InputLabel
          for="primary_income"
          value="Primary Income?"
        />
        <Checkbox
          id="primary_income"
          v-model:checked="form.primary_income"
          name="primary_income"
        />
        <InputError
          class="mt-2"
          :message="form.errors.primary_income"
        />
      </div>

      <div class="m-4">
        <InputLabel
          for="extra_income"
          value="Extra Income?"
        />
        <Checkbox
          id="extra_income"
          v-model:checked="form.extra_income"
          name="extra_income"
        />
        <InputError
          class="mt-2"
          :message="form.errors.extra_income"
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
<style>

</style>
