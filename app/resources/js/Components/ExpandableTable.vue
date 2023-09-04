<script setup>
  import {
    onBeforeUpdate,
    computed,
    reactive,
    watch,
    ref
  } from 'vue';
  import { sort } from 'fast-sort'
  const emit = defineEmits(['row-expanded', 'row-collapsed']);

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
    },
    expand: {
      type: Boolean,
      default: () => true
    },
    paginationStart: {
      type: Number,
      default: () => 10
    }
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
  const maxThWidthClass = computed(() => `max-w-[${1 / fieldCount.value}]`);

  const getSpecialRowClasses = (item) => {
    if (item.overdrawn_or_overpaid) {
      if (item.asset) {
        return 'border-l-8 border-solid border-l-red-800';
      } else {
        return 'border-l-8 border-solid border-l-green-800';
      }
    }
  };

  const showHideRow = (item, i) => {
    if (! props.expand) {
      return;
    }
    let hiddenRow = hiddenTrRefs.value[i];
    if (hiddenRow && hiddenRow.classList.contains("hidden")) {
      hiddenRow.classList.remove("hidden");
      emit('row-expanded', hiddenRow, i);
    } else if (hiddenRow) {
      hiddenRow.classList.add("hidden");
      emit('row-collapsed', hiddenRow, i);
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
  <div class="max-h-[48rem] relative">
    <div v-if="items.length > 0">
      <div
        v-if="items.length > paginationStart"
        class="py-2 min-w-full sm:px-6 lg:px-8"
      >
        <div
          class="m-1"
          style="text-align: right"
        >
          Results per page
          <button
            class="mx-2 p-1"
            :class="{'border border-round border-black border-solid': perPage === 10}"
            @click="perPage = 10"
          >
            10
          </button>
          <button
            class="mx-2 p-1"
            :class="{'border border-round border-black border-solid': perPage === 20}"
            @click="perPage = 20"
          >
            20
          </button>
          <button
            class="mx-2 p-1"
            :class="{'border border-round border-black border-solid': perPage === 50}"
            @click="perPage = 50"
          >
            50
          </button>
          <button
            class="mx-2 p-1"
            :class="{'border border-round border-black border-solid': perPage === 100}"
            @click="perPage = 100"
          >
            100
          </button>
          <button
            class="mx-2 p-1"
            :class="{'border border-round border-black border-solid': perPage === 200}"
            @click="perPage = 200"
          >
            200
          </button>
        </div>

        <div
          class="m-1 flex flex-row-reverse"
          style="text-align: right"
        >
          <div v-if="pagination.totalPages > 1">
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

          <div class="mr-3">
            {{ items.length }} total transactions
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
              class="sortable text-lg font-semibold bg-zinc-200 dark:bg-slate-800"
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
              class="text-lg font-semibold bg-zinc-200 dark:bg-slate-800"
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
            class="hover:bg-slate-300 dark:hover:bg-slate-600 focus:bg-slate-400 border-t"
            :class="getSpecialRowClasses(item)"
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
            v-if="expand && item.expand"
            :ref="(el) => { hiddenTrRefs.push(el) }"
            :id="`hidden_row_${i}`"
            class="hidden"
            :class="getSpecialRowClasses(item)"
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

    <div v-if="items.length > 0">
      <div
        v-if="items.length > paginationStart"
        class="py-2 min-w-full sm:px-6 lg:px-8"
      >
        <div
          class="m-1"
          style="text-align: right"
        >
          Results per page
          <button
            class="mx-2 p-1"
            :class="{'border border-round border-black border-solid': perPage === 10}"
            @click="perPage = 10"
          >
            10
          </button>
          <button
            class="mx-2 p-1"
            :class="{'border border-round border-black border-solid': perPage === 20}"
            @click="perPage = 20"
          >
            20
          </button>
          <button
            class="mx-2 p-1"
            :class="{'border border-round border-black border-solid': perPage === 50}"
            @click="perPage = 50"
          >
            50
          </button>
          <button
            class="mx-2 p-1"
            :class="{'border border-round border-black border-solid': perPage === 100}"
            @click="perPage = 100"
          >
            100
          </button>
          <button
            class="mx-2 p-1"
            :class="{'border border-round border-black border-solid': perPage === 200}"
            @click="perPage = 200"
          >
            200
          </button>
        </div>

        <div
          class="m-1 flex flex-row-reverse"
          style="text-align: right"
        >
          <div v-if="pagination.totalPages > 1">
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

          <div class="mr-3">
            {{ items.length }} total transactions
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style>
  table {
    position: relative;
    border-collapse: collapse;
  }

  th {
    position: sticky;
    top: -1px; /* Don't forget this, required for the stickiness */
    box-shadow: 0 2px 2px -1px rgba(0, 0, 0, 0.4);
  }

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

