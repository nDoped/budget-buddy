<script setup>
  import {  ref } from 'vue';
  import AppLayout from '@/Layouts/AppLayout.vue';
  import SettingsNavMenu from '@/Components/SettingsNavMenu.vue';
  import ExpandableTable from '@/Components/ExpandableTable.vue';
  import CategoryEditForm from '@/Components/CategoryEditForm.vue';
  import CategoryForm from '@/Components/CategoryForm.vue';

  defineProps({
    categories: {
      type: Array,
      default: () => {}
    },
    categoryTypes: {
      type: Array,
      default: () => {}
    }
  });

  const fields = ref([
    //{ key: 'id', label: 'ID', sortable: true },
    { key: 'name', label: 'Name', sortable: true },
    { key: 'category_type_name', label: 'Type', sortable: true },
    { key: 'hex_color', label: 'Color', sortable: true  },
    { key: 'active_text', label: 'Active', sortable: true  },
  ]);

  const hideTr = (hiddenTrRefs, i) => {
    hiddenTrRefs[i].classList.add("hidden");
  };

  const cellBackground = (item) => {
    return `background-color: ${item.hex_color}`
  }
</script>

<template>
  <AppLayout title="Settings - Categories">
    <div class="max-w-8xl mx-auto py-10 sm:px-6 lg:px-8">
      <SettingsNavMenu />

      <div class="overflow-x-auto sm:-mx-6 lg:-mx-8">
        <div class="py-2 inline-block min-w-full sm:px-6 lg:px-8">
          <div class="overflow-hidden">
            <CategoryForm :category-types="categoryTypes" />
          </div>
        </div>
      </div>

      <div class="overflow-x-auto">
        <ExpandableTable
          :items="categories"
          :fields="fields"
          class="grow w-full bg-gray-800 text-slate-300"
        >
          <template #visible_row="{ item , key }">
            <div v-if="key === 'hex_color'">
              <span :style="cellBackground(item)">
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
              </span>
            </div>
          </template>

          <template #hidden_row="{hidden_tr_refs, item, i}">
            <CategoryEditForm
              :category="item"
              :category-types="categoryTypes"
              @cancel="hideTr(hidden_tr_refs, i)"
              @success="hideTr(hidden_tr_refs, i)"
            />
          </template>
        </ExpandableTable>
      </div>
    </div>
  </AppLayout>
</template>
