<script setup>
  import AppLayout from '@/Layouts/AppLayout.vue';
  import { Link } from '@inertiajs/vue3';
  import { ref } from 'vue';

  defineProps({
    logs: {
      type: Object,
      default: () => ({})
    }
  });

  const expandedRows = ref([]);

  function toggleRow(id) {
    const idx = expandedRows.value.indexOf(id);
    if (idx === -1) {
      expandedRows.value.push(id);
    } else {
      expandedRows.value.splice(idx, 1);
    }
  }

  function hasChanges(log) {
    return log.properties && Object.keys(log.properties).length > 0
      && log.properties[Object.keys(log.properties)[0]]?.old !== undefined;
  }

  function changeEntries(log) {
    if (!log.properties) return [];
    return Object.entries(log.properties)
      .filter(([, v]) => v && typeof v === 'object' && 'old' in v && 'new' in v)
      .map(([field, v]) => ({
        field: fieldLabel(field),
        old: formatValue(field, v.old),
        new: formatValue(field, v.new),
      }));
  }

  function fieldLabel(field) {
    const labels = {
      amount: 'Amount',
      account_id: 'Account',
      credit: 'Type',
      transaction_date: 'Date',
      note: 'Note',
      name: 'Name',
      hex_color: 'Color',
      category_type_id: 'Category Type',
      category_subtype_id: 'Category Subtype',
      active: 'Active',
      type_id: 'Account Type',
      interest_rate: 'Interest Rate',
      initial_balance: 'Initial Balance',
      url: 'URL',
      categories: 'Categories',
    };
    return labels[field] || field;
  }

  function formatValue(field, value) {
    if (value === null || value === undefined) return '—';
    if (Array.isArray(value)) {
      if (value.length === 0) return '(none)';
      return value.map(v => {
        if (typeof v === 'object' && v.name) {
          return v.name + (v.percentage != null ? ' (' + v.percentage + '%)' : '');
        }
        return String(v);
      }).join(', ');
    }
    if (field === 'amount' || field === 'initial_balance') {
      return '$' + (value / 100).toFixed(2);
    }
    if (field === 'credit') return value ? 'Credit' : 'Debit';
    if (field === 'active') return value ? 'Yes' : 'No';
    if (field === 'interest_rate') return value != null ? value + '%' : '—';
    return String(value);
  }

  function formatDate(dateStr) {
    const d = new Date(dateStr);
    return d.toLocaleString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    });
  }

  function logTypeLabel(type) {
    const labels = {
      transaction_created: 'Transaction Created',
      transaction_updated: 'Transaction Updated',
      transaction_deleted: 'Transaction Deleted',
      category_created: 'Category Created',
      category_updated: 'Category Updated',
      category_merged: 'Category Merged',
      category_deleted: 'Category Deleted',
      category_type_created: 'Category Type Created',
      category_type_updated: 'Category Type Updated',
      category_type_deleted: 'Category Type Deleted',
      account_created: 'Account Created',
      account_updated: 'Account Updated',
      account_deleted: 'Account Deleted',
      account_type_created: 'Account Type Created',
    };
    return labels[type] || type;
  }
</script>

<template>
  <AppLayout title="Activity Log">
    <template #header>
      <h2 class="font-semibold text-xl text-slate-900 leading-tight">
        Activity Log
      </h2>
    </template>

    <div class="py-6">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-slate-500 overflow-hidden shadow-xl sm:rounded-lg">
          <div class="p-6">
            <div v-if="logs.data && logs.data.length">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-slate-600">
                  <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-slate-300 uppercase tracking-wider">
                      Date
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-slate-300 uppercase tracking-wider">
                      Action
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-slate-300 uppercase tracking-wider">
                      Description
                    </th>
                  </tr>
                </thead>
                <tbody class="bg-slate-500 divide-y divide-slate-600">
                  <template
                    v-for="log in logs.data"
                    :key="log.id"
                  >
                    <tr
                      class="hover:bg-slate-400 cursor-pointer"
                      @click="toggleRow(log.id)"
                    >
                      <td class="px-4 py-3 whitespace-nowrap text-sm text-slate-200">
                        {{ formatDate(log.created_at) }}
                      </td>
                      <td class="px-4 py-3 whitespace-nowrap text-sm">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-slate-700 text-slate-200">
                          {{ logTypeLabel(log.log_type) }}
                        </span>
                      </td>
                      <td class="px-4 py-3 text-sm text-slate-200">
                        <div class="flex items-center gap-2">
                          <span>{{ log.description }}</span>
                          <span
                            v-if="hasChanges(log)"
                            class="text-xs text-slate-400"
                          >
                            ({{ expandedRows.includes(log.id) ? 'hide' : 'details' }})
                          </span>
                        </div>
                        <div
                          v-if="hasChanges(log) && expandedRows.includes(log.id)"
                          class="mt-2 space-y-1"
                        >
                          <div
                            v-for="change in changeEntries(log)"
                            :key="change.field"
                            class="text-xs"
                          >
                            <span class="text-slate-400">{{ change.field }}:</span>
                            <span class="line-through text-red-300 ml-1">{{ change.old }}</span>
                            <span class="text-slate-400 mx-1">→</span>
                            <span class="text-green-300">{{ change.new }}</span>
                          </div>
                        </div>
                      </td>
                    </tr>
                  </template>
                </tbody>
              </table>

              <div
                v-if="logs.links"
                class="flex items-center justify-between px-4 py-3 bg-slate-500 sm:px-6"
              >
                <div class="flex-1 flex justify-between">
                  <Link
                    v-if="logs.prev_page_url"
                    :href="logs.prev_page_url"
                    class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-slate-200 bg-slate-600 rounded-md hover:bg-slate-400"
                  >
                    Previous
                  </Link>
                  <span v-else />
                  <Link
                    v-if="logs.next_page_url"
                    :href="logs.next_page_url"
                    class="relative inline-flex items-center px-4 py-2 ml-3 text-sm font-medium text-slate-200 bg-slate-600 rounded-md hover:bg-slate-400"
                  >
                    Next
                  </Link>
                </div>
              </div>
            </div>
            <div v-else>
              <p class="text-slate-300 text-center py-8">
                No activity logged yet.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
