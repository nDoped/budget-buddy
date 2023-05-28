<script setup>
  import InputLabel from "@/Components/InputLabel.vue";
  import { ref } from "vue";
  import InputError from "@/Components/InputError.vue";
  import PrimaryButton from "@/Components/PrimaryButton.vue";
  import { toast } from "vue3-toastify";
  import TextInput from "@/Components/TextInput.vue";
  import { useForm } from "@inertiajs/vue3";
  import ExpandableTable from "@/Components/ExpandableTable.vue";

  defineProps({
    accountTypes: {
      type: Object,
      default: () => {},
    },
  });

  const success = () => {
    toast.success("Account Type Created!");
    form.reset();
  };

  function submit() {
    /* global route */
    form.post(route("account_types.store"), {
      preserveScroll: true,
      onSuccess: success,
    });
  }

  const fields = ref([
    { key: "name", label: "Name" },
    { key: "asset", label: "Asset/Debt" },
  ]);

  const form = useForm({
    name: null,
    asset: null,
  });
</script>

<template>
  <div class="overflow-hidden">
    <form @submit.prevent="submit">
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
            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
            required
          />
          <InputError
            :message="form.errors.name"
            class="mt-2"
          />
        </div>

        <div class="m-4">
          <InputLabel
            for="asset"
            value="Asset/Debt"
          />
          <select
            id="asset"
            v-model="form.asset"
            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
          >
            <option
              selected="selected"
              value="true"
            >
              Asset
            </option>
            <option value="false">
              Debt
            </option>
          </select>
          <InputError
            :message="form.errors.type"
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
          Add Account Type
        </PrimaryButton>
      </div>
    </form>
  </div>

  <div class="overflow-hidden">
    <ExpandableTable
      class="grow w-full bg-gray-800 text-slate-300"
      :items="accountTypes"
      :fields="fields"
      :expand="false"
    >
      <template #visible_row="{ value }">
        <div class="font-semibold text-l">
          {{ value }}
        </div>
      </template>
    </ExpandableTable>
  </div>
</template>
