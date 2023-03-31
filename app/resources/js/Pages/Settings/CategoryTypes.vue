<script setup>
  import {  ref } from 'vue';
  import AppLayout from '@/Layouts/AppLayout.vue';
  import SettingsNavMenu from '@/Components/SettingsNavMenu.vue';
  import ExpandableTable from '@/Components/ExpandableTable.vue';
  import CategoryTypeEditForm from '@/Components/CategoryTypeEditForm.vue';
  import CategoryTypeForm from '@/Components/CategoryTypeForm.vue';

  defineProps({
    categoryTypes: {
      type: Array,
      default: () => []
    }
  });

  const fields = ref([
    { key: 'name', label: 'Name', sortable: true },
    { key: 'note', label: 'Note', sortable: true  },
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
            <CategoryTypeForm />
          </div>
        </div>
      </div>

      <div class="overflow-x-auto">
        <ExpandableTable
          :items="categoryTypes"
          :fields="fields"
          class="grow w-full bg-gray-800 text-slate-300"
        >
          <template #visible_row="{ item , key }">
            <div v-if="key === 'color'">
              <span :style="cellBackground(item)">
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
              </span>
            </div>
          </template>

          <template #hidden_row="{ hidden_tr_refs, item, i }">
            <CategoryTypeEditForm
              :category-type="item"
              @cancel="hideTr(hidden_tr_refs, i)"
              @success="hideTr(hidden_tr_refs, i)"
            />
          </template>
        </ExpandableTable>
      </div>
    </div>
  </AppLayout>
</template>
