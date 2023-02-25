<script setup>
  import { inject, onBeforeUpdate, computed, reactive, watch, ref } from 'vue';
  import { sort } from 'fast-sort'
  const formatter = inject('formatter');

  const sortBy = ref(null);
  const sortDesc = ref(null);
  const props = defineProps({
    items: Array,
    fields: Object
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

  const fieldCount = ref(Object.keys(props.fields).length);
  const maxThWidthClass = computed(() => `max-w-[${1 / fieldCount}]`);

  const tableRowCss = (item, i) => {
    return 'border-t';
  };

  const showHideRow = (item, i) => {
    let hiddenRow = document.getElementById(`hidden_row_${i}_${Object.values(item).join('-')}`);
    let visibleRow = document.getElementById(`visible_row_${i}_${Object.values(item).join('-')}`);
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
</script>


<template>
  <div>
    <div v-if="items.length > 0" class="overflow-x-auto sm:-mx-6 lg:-mx-8">
      <div v-if="pagination.totalPages > 1" class="py-2 min-w-full sm:px-6 lg:px-8">
        <div class="m-1" >
          Results per page
          <button class="mx-2" @click="perPage = 5">5</button>
          <button class="mx-2" @click="perPage = 10">10</button>
          <button class="mx-2" @click="perPage = 20">20</button>
          <button class="mx-2" @click="perPage = 50">50</button>
        </div>

        <div class="m-1" style="text-align: right">
          <div>
            <button
              :disabled="pagination.currentPage <= 1"
              @click="pagination.currentPage--"
            >
              &lt;&lt;
            </button>
            Page {{ pagination.currentPage }} of {{ pagination.totalPages }}
            <button
              :disabled="pagination.currentPage >= pagination.totalPages"
              @click="pagination.currentPage++"
            >
              &gt;&gt;
            </button>
          </div>
        </div>
      </div>
    </div>

    <table class="min-w-full table-auto">
      <thead>
        <tr>
          <template v-for="{ key, label, sortable } in fields" :key="key">
            <th v-if="sortable" @click="setSort(key)" class="sortable text-xl font-bold" :class="maxThWidthClass">
              {{ label }}
              <template v-if="sortBy === key">
                <span v-if="sortDesc === true">↑</span>
                <span v-else-if="sortDesc === false">↓</span>
              </template>
            </th>

            <th v-else :class="maxThWidthClass" class="text-xl font-bold">
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
            :id="`visible_row_${i}_${Object.values(item).join('-')}`"
            class="hover:opacity-80 focus:bg-slate-400"
            :class="tableRowCss(item, i)"
          >
            <td
              v-for="{ key } in fields"
              :key="key"
              class="text-center px-2 py-1 text-md font-medium"
            >
              <slot name="visible_row" :value="item[key]" :item="item" :key="key">
                {{ item[key] }}
              </slot>
            </td>
          </tr>

          <tr :id="`hidden_row_${i}_${Object.values(item).join('-')}`" class="hidden">
            <td :colspan="fieldCount">
              <slot name="hidden_row" :i="i" :item="item">
                  Boo!
              </slot>
            </td>
          </tr>
        </template>
      </tbody>
    </table>
  </div>
</template>

<style>
  th.sortable {
    cursor: pointer;
  }

  .text {
    overflow: hidden;
    max-height: 0;
    transition: max-height 0.5s cubic-bezier(0, 1, 0, 1);
  }

  .open_row {
    max-height: 1000px;
    transition: max-height 1s ease-in-out;
  }
</style>

