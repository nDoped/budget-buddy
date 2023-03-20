<script setup>
  import {  ref } from 'vue';
  import AppLayout from '@/Layouts/AppLayout.vue';
  import SettingsNavMenu from '@/Components/SettingsNavMenu.vue';
  import ExpandableTable from '@/Components/ExpandableTable.vue';
  import CategoryEditForm from '@/Components/CategoryEditForm.vue';
  import CategoryForm from '@/Components/CategoryForm.vue';
  //const formatter = inject('formatter');

  defineProps({
    categories: {
      type: Array,
      default: () => {}
    }
  });

  const fields = ref([
    //{ key: 'id', label: 'ID', sortable: true },
    { key: 'name', label: 'Name', sortable: true },
    { key: 'primary_income', label: 'Primary Income?', sortable: true },
    { key: 'secondary_income', label: 'Secondary Income?', sortable: true },
    { key: 'regular_expense', label: 'Regular Expense?', sortable: true },
    { key: 'recurring_expense', label: 'Recurring Expense?', sortable: true },
    { key: 'extra_expense', label: 'Extra Expense?', sortable: true },
    { key: 'housing_expense', label: 'Housing?', sortable: true },
    { key: 'utility_expense', label: 'Utilities?', sortable: true },
    { key: 'color', label: 'Color', sortable: true  },
  ]);

  const hideTr = (hiddenTrRefs, i) => {
    hiddenTrRefs[i].classList.add("hidden");
  };

  const cellBackground = (item) => {
    return `background-color: ${item.color}`
  }
</script>

<template>
  <AppLayout title="Settings - Categories">
    <div class="max-w-8xl mx-auto py-10 sm:px-6 lg:px-8">
      <SettingsNavMenu />

      <div class="overflow-x-auto sm:-mx-6 lg:-mx-8">
        <div class="py-2 inline-block min-w-full sm:px-6 lg:px-8">
          <div class="overflow-hidden">
            <CategoryForm />
          </div>
        </div>
      </div>

      <div class="overflow-x-auto">
        <ExpandableTable
          :items="categories"
          :fields="fields"
          class="grow w-full bg-gray-800 text-slate-300"
        >
          <template #visible_row="{ item , value, key }">
            <div v-if="key === 'color'">
              <span :style="cellBackground(item)">
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
              </span>
            </div>

            <div v-else-if="['primary_income', 'secondary_income', 'regular_expense', 'recurring_expense', 'extra_expense', 'housing_expense', 'utility_expense'].includes(key)">
              <span v-if="value" class="text-green-400">
                ✔️
              </span>
            </div>
          </template>

          <template #hidden_row="{hidden_tr_refs, item, i}">
            <CategoryEditForm
              :category="item"
              @cancel="hideTr(hidden_tr_refs, i)"
              @success="hideTr(hidden_tr_refs, i)"
            />
          </template>
        </ExpandableTable>
      </div>
    </div>
  </AppLayout>
</template>
<style>

</style>
