<script setup>
  import { ref } from 'vue';
  import AppLayout from '@/Layouts/AppLayout.vue';
  import SettingsNavMenu from '@/Components/SettingsNavMenu.vue';
  import ExpandableTable from '@/Components/ExpandableTable.vue';
  import CategorySubtypeEditForm from '@/Components/CategorySubtypeEditForm.vue';
  import CategorySubtypeForm from '@/Components/CategorySubtypeForm.vue';

  defineProps({
    categorySubtypes: {
      type: Array,
      default: () => []
    },
    categoryTypes: {
      type: Array,
      default: () => []
    }
  });

  const fields = ref([
    { key: 'name', label: 'Name', sortable: true },
    { key: 'category_type_name', label: 'Category Type', sortable: true },
  ]);

  const hideTr = (hiddenTrRefs, i) => {
    hiddenTrRefs[i].classList.add("hidden");
  };
</script>

<template>
  <AppLayout title="Settings - Category Subtypes">
    <div class="max-w-8xl mx-auto py-10 sm:px-6 lg:px-8">
      <SettingsNavMenu />

      <div class="overflow-x-auto sm:-mx-6 lg:-mx-8">
        <div class="py-2 inline-block min-w-full sm:px-6 lg:px-8">
          <div class="overflow-hidden">
            <CategorySubtypeForm :category-types="categoryTypes" />
          </div>
        </div>
      </div>

      <div class="overflow-x-auto">
        <ExpandableTable
          :items="categorySubtypes"
          :fields="fields"
          class="grow w-full bg-gray-800 text-slate-300"
        >
          <template #hidden_row="{ hidden_tr_refs, item, i }">
            <CategorySubtypeEditForm
              :category-subtype="item"
              @cancel="hideTr(hidden_tr_refs, i)"
              @success="hideTr(hidden_tr_refs, i)"
            />
          </template>
        </ExpandableTable>
      </div>
    </div>
  </AppLayout>
</template>
