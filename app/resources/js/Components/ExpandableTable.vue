<script setup>
  import { inject, onBeforeUpdate, computed, reactive, watch, ref } from 'vue';
  import { sort } from 'fast-sort'
  const formatter = inject('formatter');

  const sortBy = ref(null);
  const sortDesc = ref(null);
  const props = defineProps({
    items: Array
  });
  const sortedItems = computed(() => {
    const { items } = props;
    if (sortDesc.value === null) return items;

    if (sortDesc.value) {
      return sort(items).desc(sortBy.value);
    } else {
      return sort(items).asc(sortBy.value);
    }
  });

  const tableDataCss = (item, color_bg) => {
    console.log(item);
    console.log(color_bg);
    let ret = (color_bg) ? `backgroundColor: 'bg-[${item['color']}]'` : '';
    //ret = (color_bg) ? `bg-[${item['color']}]` : '';

    console.log(ret);
    return ret;
  };


  const tableRowCss = (item, i) => {
    return 'border-b';
  };

  const showHideRow = (trans, i) => {
    let hiddenRow = document.getElementById(`hidden_row_${i}`);
    let visibleRow = tableRowRefs.value[i];
    let nextVisibleRow = tableRowRefs.value[i + 1];
    let hiddenRowClasses = [
      "open_row",
    ];
    if (hiddenRow.classList.contains("hidden")) {
      document.getElementById(`hidden_row_${i}`).classList.remove("hidden");
      visibleRow.classList.remove("border-b");
      if (nextVisibleRow) {
        nextVisibleRow.classList.add("border-t");
      }
      /**
       * Force a browser re-paint so the browser will realize the
       * element is no longer `hidden` and allow transitions.
       */
      const reflow = hiddenRow.offsetHeight;

      hiddenRow.classList.add(...hiddenRowClasses);
    } else {
      const reflow = hiddenRow.offsetHeight;
      hiddenRow.classList.add("hidden");
      visibleRow.classList.add("border-b");
      if (nextVisibleRow) {
        nextVisibleRow.classList.remove("border-t");
      }
    }
  };

  const setSort = (key) => {
    if (sortBy.value === key) {
      sortDesc.value = ! sortDesc.value;
    } else {
      sortBy.value = key;
      sortDesc.value = false;
    }
  };

  const perPage = ref(20);
  const pagination = reactive({
    currentPage: 1,
    perPage: perPage,
    totalPages: computed(() =>
      Math.ceil(props.items.length / pagination.perPage)
    ),
  });

  const paginatedItems = computed(() => {
    const { currentPage, perPage } = pagination;
    const start = (currentPage - 1) * perPage;
    const stop = start + perPage;

    return sortedItems.value.slice(start, stop);
  });

  watch(
    () => pagination.totalPages,
    () => (pagination.currentPage = 1)
  );

  // make sure to reset the refs before each update
  const tableRowRefs = ref([]);
  onBeforeUpdate(() => {
    tableRowRefs.value = []
  });
  const fields = ref([
    { key: 'id', label: 'ID', sortable: true },
    { key: 'name', label: 'Name', sortable: true },
    { key: 'include_in_expense_breakdown_text', label: 'Show in expense breakdown chart?', sortable: true },
    { key: 'color', label: 'Color', sortable: true, color_bg:true },
  ]);
</script>


<template>
  <table class="min-w-full table-auto">
    <thead>
      <tr>
        <template v-for="{ key, label, sortable } in fields" :key="key">
          <th v-if="sortable" @click="setSort(key)" class="sortable">
            {{ label }}
            <template v-if="sortBy === key">
              <span v-if="sortDesc === true">↑</span>
              <span v-else-if="sortDesc === false">↓</span>
            </template>
          </th>

          <th v-else>
            {{ label }}
          </th>
        </template>
      </tr>
    </thead>

    <tbody>
      <template v-for="(item, i) in paginatedItems" :key="i">
        <tr
          @click="showHideRow(item, i)"
          :ref="(el) => { tableRowRefs.push(el) }"
          class="hover:opacity-80 focus:bg-slate-400"
          :class="tableRowCss(item, i)"
        >
          <td
            v-for="{ key } in fields"
            :key="key"
            class="text-center px-2 py-1 text-md font-medium"
            :style="{ 'backgroundColor': (color_bg) ? item[key] : '' }"
          >
            <slot :name="`cell(${key})`" :value="item[key]" :item="item">
              {{ item[key] }}
            </slot>
          </td>
        </tr>

        <slot :name="`cell(${key})-hidden`" :value="item[key]">
          <tr :id="`hidden_row_${i}`" class="hidden">
            <td colspan="7" >
              Boo!
            </td>
          </tr>
        </slot>
      </template>
    </tbody>
  </table>
</template>
