<script setup>
  import { Head, Link } from '@inertiajs/vue3';
  import { inject, onBeforeUpdate, computed, reactive, watch, ref } from 'vue';
  import { sort } from 'fast-sort'
  import AppLayout from '@/Layouts/AppLayout.vue';
  import SectionBorder from '@/Components/SectionBorder.vue';
  import SettingsNavMenu from '@/Components/SettingsNavMenu.vue';
  import ExpandableTable from '@/Components/ExpandableTable.vue';
  import CategoryEditForm from '@/Components/CategoryEditForm.vue';
  //const formatter = inject('formatter');

  const props = defineProps({
    categories: Array
  });

  const fields = ref([
    { key: 'id', label: 'ID', sortable: true },
    { key: 'name', label: 'Name', sortable: true },
    { key: 'include_in_expense_breakdown_text', label: 'Show in expense breakdown chart?', sortable: true },
    { key: 'color', label: 'Color', sortable: true, color_bg:true },
  ]);

  const showHideRow = (item, i) => {
    let hiddenRow = document.getElementById(`hidden_row_${i}_${item}`);
    let visibleRow = document.getElementById(`visible_row_${i}_${item}`);
    /*
    let hiddenRow = document.getElementById(`hidden_row_${i}_${Object.values(item).join('-')}`);
    let visibleRow = document.getElementById(`visible_row_${i}_${Object.values(item).join('-')}`);
     */
    if (hiddenRow.classList.contains("hidden")) {
      hiddenRow.classList.remove("hidden");
      // Force a browser re-paint so the browser will realize the
      // element is no longer `hidden` and allow transitions.
      const reflow = hiddenRow.offsetHeight;
    } else {
      const reflow = hiddenRow.offsetHeight;
      hiddenRow.classList.add("hidden");
    }

  };
  const colorBg = (key, value, item) => {
    let test = fields.value.find(field => field.key === key );
    if (test.color_bg) {

      console.log(item);
      //return "backgroundColor: `#${item.color}`";
      return true;
      //return styleObject;
    }
    return false;
  };
</script>

<template>
  <AppLayout title="Settings - Categories">
    <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
      <SettingsNavMenu />
      <div class="overflow-hidden">
        <ExpandableTable :items="categories" :fields="fields">
          <template #visible_row="{ item , value, key }">
            <div v-if="colorBg(key, value, item)">
              <span :class="`bg-[${item.color}]`">{{ value }}</span>
            </div>

          </template>

          <template #hidden_row="{item, i}">
            <CategoryEditForm
              :category="item"
              @success="showHideRow(item, i)"
            />
          </template>
        </ExpandableTable>
      </div>
    </div>
  </AppLayout>
</template>
<style>

</style>
