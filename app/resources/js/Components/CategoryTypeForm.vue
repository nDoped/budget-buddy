<script setup>
  import { useForm } from '@inertiajs/vue3'
  import { toast } from 'vue3-toastify';
  import InputLabel from '@/Components/InputLabel.vue';
  import InputError from '@/Components/InputError.vue';
  import TextArea from '@/Components/TextArea.vue';
  import PrimaryButton from '@/Components/PrimaryButton.vue';
  import TextInput from '@/Components/TextInput.vue';

  const success = () => {
    toast.success('Category Type Created!');
    form.reset();
  };

  const form = useForm({
    name: null,
    note: null,
    color: '#000000'
  });
  function submit() {
    /* global route */
    form.post(route('category_type.store'), {
      preserveScroll: true,
      onSuccess: success,
      onError: (err) =>  {
        console.error(err)
      }
    });
  }

  const uuid = crypto.randomUUID();
  const getUuid = (el) => {
    return `${el}-${uuid}`;
  };
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
          :for="getUuid('catt-color')"
          value="color"
        />
        <input
          :id="getUuid('catt-color')"
          type="color"
          v-model="form.color"
        >
      </div>

      <div class="m-2">
        <InputLabel
          :for="getUuid('catt-note')"
          value="Note"
        />
        <TextArea
          :id="getUuid('catt-note')"
          v-model="form.note"
          type="text"
          class="mt-1 block w-full"
          autocomplete="note"
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
