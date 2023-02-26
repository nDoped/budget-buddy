<script setup>
  import { onBeforeUpdate, computed, reactive, watch, ref } from 'vue';
  import { sort } from 'fast-sort'

  const sortBy = ref(null);
  const sortDesc = ref(null);
  const props = defineProps({
    items: {
      type: Array,
      default: () => {}
    },
    fields: {
      type: Object,
      default: () => {}
    }
  });

  watch(
    () => props.items,
    (props) => {
      console.log('expandabletable props watch', props);
    }
  );

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
  const maxThWidthClass = computed(() => `max-w-[${1 / fieldCount.value}]`);

  const showHideRow = (item, i) => {
    let hiddenRow = hiddenTrRefs.value[i];
    if (hiddenRow.classList.contains("hidden")) {
      hiddenRow.classList.remove("hidden");
    } else {
      hiddenRow.classList.add("hidden");
    }

    /*
    if (! hiddenRow.classList.contains("active")) {
      if (hiddenRow.classList.contains("deactive")) {
        hiddenRow.classList.remove("deactive");
      }
      hiddenRow.classList.add("active");
    } else {
      hiddenRow.classList.remove("active");
      hiddenRow.classList.add("deactive");
    }
    */
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
  const visibleTrRefs = ref([]);
  const hiddenTrRefs = ref([]);
  onBeforeUpdate(() => {
    visibleTrRefs.value = []
    hiddenTrRefs.value = []
  });
</script>

<template>
  <div>
    <div
      v-if="items.length > 0"
      class="overflow-x-auto sm:-mx-6 lg:-mx-8"
    >
      <div
        v-if="pagination.totalPages > 1"
        class="py-2 min-w-full sm:px-6 lg:px-8"
      >
        <div class="m-1">
          Results per page
          <button
            class="mx-2"
            @click="perPage = 5"
          >
            5
          </button>
          <button
            class="mx-2"
            @click="perPage = 10"
          >
            10
          </button>
          <button
            class="mx-2"
            @click="perPage = 20"
          >
            20
          </button>
          <button
            class="mx-2"
            @click="perPage = 50"
          >
            50
          </button>
        </div>

        <div
          class="m-1"
          style="text-align: right"
        >
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

    <table class="min-w-full table-auto text-black bg-zinc-200 dark:text-white dark:bg-slate-800">
      <thead>
        <tr>
          <template
            v-for="{ key, label, sortable } in fields"
            :key="key"
          >
            <th
              v-if="sortable"
              @click="setSort(key)"
              class="sortable text-xl font-bold"
              :class="maxThWidthClass"
            >
              {{ label }}
              <template v-if="sortBy === key">
                <span v-if="sortDesc === true">↑</span>
                <span v-else-if="sortDesc === false">↓</span>
              </template>
            </th>

            <th
              v-else
              :class="maxThWidthClass"
              class="text-xl font-bold"
            >
              <span class="text-zinc-800 dark:text-zinc-200">
                {{ label }}
              </span>
            </th>
          </template>
        </tr>
      </thead>

      <tbody>
        <template
          v-for="(item, i) in paginatedItems"
          :key="i"
        >
          <tr
            @click="showHideRow(item, i)"
            :ref="(el) => { visibleTrRefs.push(el) }"
            :id="`visible_row_${i}`"
            class="hover:opacity-80 focus:bg-slate-400 border-t border-zinc-900 dark:border-zinc-100"
          >
            <td
              v-for="{ key } in fields"
              :key="key"
              class="text-center px-2 py-1 text-md font-medium text-zinc-800 dark:text-slate-100"
            >
              <slot
                name="visible_row"
                :value="item[key]"
                :item="item"
                :key="key"
              >
                {{ item[key] }}
              </slot>
            </td>
          </tr>

          <tr
            :ref="(el) => { hiddenTrRefs.push(el) }"
            :id="`hidden_row_${i}`"
            class="hidden"
          >
            <td :colspan="fieldCount">
              <slot
                name="hidden_row"
                :i="i"
                :item="item"
                :hidden_tr_refs="hiddenTrRefs"
              >
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

  .outer {
    overflow: hidden;
    position: relative;
  }

  .content-page {
    position:absolute;
    z-index: -1;
    overflow:hidden;
    right:-50000px;
    opacity: 0;
  }
  /*
    opacity: 0;
    transition: opacity 1s linear;
    opacity: 1;
    transition: opacity 1s linear;
  */

  .content-page.active {
    position: static;
    height: 100px;
    opacity: 1;
    transition: all 2s ease-in-out;
  }

  .content-page.deactive {
    position: absolute;
    right:-50px;
    z-index: -1;
    height: 0;
    opacity: 0;
    transition: all 2s ease-in-out;
  }
</style>

