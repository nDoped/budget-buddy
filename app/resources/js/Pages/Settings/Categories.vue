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
    { key: 'id', label: 'ID', sortable: true },
    { key: 'name', label: 'Name', sortable: true },
    { key: 'extra_expense_text', label: 'Extra Expense?', sortable: true },
    { key: 'recurring_expense_text', label: 'Recurring Expense?', sortable: true },
    { key: 'color', label: 'Color', sortable: true, color_bg:true },
  ]);

  const hideTr = (hiddenTrRefs, i) => {
    hiddenTrRefs[i].classList.add("hidden");
  };

  const colorBg = (key) => {
    let test = fields.value.find(field => field.key === key );
    if (test.color_bg) {
      return true;
    }
    return false;

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

      <div class="overflow-hidden">
        <ExpandableTable
          :items="categories"
          :fields="fields"
          class="grow w-full bg-gray-800 text-slate-300"
        >
          <template #visible_row="{ item , value, key }">
            <div v-if="colorBg(key, value, item)">
              <span :style="cellBackground(item)">
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
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
