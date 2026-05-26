<script setup>
  import { ref, watch } from 'vue';
  import { router } from '@inertiajs/vue3';
  import AppLayout from '@/Layouts/AppLayout.vue';
  import SettingsNavMenu from '@/Components/SettingsNavMenu.vue';
  import ExpandableTable from '@/Components/ExpandableTable.vue';
  import CategoryEditForm from '@/Components/CategoryEditForm.vue';
  import CategoryForm from '@/Components/CategoryForm.vue';

  const props = defineProps({
    categories: {
      type: Array,
      default: () => {}
    },
    categoryTypes: {
      type: Array,
      default: () => {}
    },
    allCategories: {
      type: Array,
      default: () => []
    }
  });

  const fields = ref([
    //{ key: 'id', label: 'ID', sortable: true },
    { key: 'name', label: 'Name', sortable: true },
    { key: 'category_type_name', label: 'Type', sortable: true },
    { key: 'hex_color', label: 'Color', sortable: true  },
    { key: 'active_text', label: 'Active', sortable: true  },
  ]);

  const search = ref('');
  const categoryType = ref('');

  let debounceTimer;
  const fetchFiltered = () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
      router.get(route('settings.categories'), {
        search: search.value || undefined,
        category_type_id: categoryType.value || undefined,
      }, {
        preserveState: true,
        preserveScroll: true,
      });
    }, 300);
  };

  watch(search, fetchFiltered);
  watch(categoryType, fetchFiltered);

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

      <div class="overflow-x-auto sm:-mx-6 lg:-mx-8">
        <div class="py-2 inline-block min-w-full sm:px-6 lg:px-8">
          <div class="flex gap-2 py-2">
            <input
              v-model="search"
              type="text"
              placeholder="Search categories..."
              class="w-full px-4 py-2 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100"
            />
            <select
              v-model="categoryType"
              class="px-4 py-2 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100"
            >
              <option value="">All Types</option>
              <option
                v-for="ct in categoryTypes"
                :key="ct.id"
                :value="ct.id"
              >
                {{ ct.name }}
              </option>
            </select>
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
              :all-categories="allCategories"
              @cancel="hideTr(hidden_tr_refs, i)"
              @success="hideTr(hidden_tr_refs, i)"
            />
          </template>
        </ExpandableTable>
      </div>
    </div>
  </AppLayout>
</template>
