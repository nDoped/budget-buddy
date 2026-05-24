<script setup>
  import { ref, inject, computed } from 'vue';
  import ExpandableTable from '@/Components/ExpandableTable.vue';
  import AccountUrlLink from '@/Components/AccountUrlLink.vue';
  import AccountEditForm from '@/Components/AccountEditForm.vue';

  const formatter = inject('formatter');

  const props = defineProps({
    accounts: {
      type: Object,
      default: () => {}
    },
    accountTypes: {
      type: Object,
      default: () => {}
    }
  });

  const hasUrl = (key) => {
    let test = fields.value.find(field => field.key === key );
    if (test.has_url) {
      return true;
    }
    return false;
  };
  const formatField = (key) => {
    let test = fields.value.find(field => field.key === key );
    if (test.format) {
      return true;
    }
    return false;
  };

  const fields = ref([
    { key: 'name', label: 'Name', sortable: true, has_url:true },
    { key: 'type', label: 'Account Type', sortable: true },
    { key: 'interest_rate', label: 'Interest Rate' },
    { key: 'initial_balance', label: 'Initial Balance', sortable:true, format:true },
    { key: 'active', label: 'Active', sortable: true },
    { key: 'url', label: 'URL' }
  ]);

  const itemsWithExpand = computed(() => {
    if (!props.accounts) return [];
    return props.accounts.map(acct => ({
      ...acct,
      expand: true
    }));
  });

</script>

<template>
  <ExpandableTable
    class="grow w-full bg-gray-800 text-slate-300"
    :items="itemsWithExpand"
    :fields="fields"
    :expand="true"
    :pagination-start="100"
  >
    <template #visible_row="{ item , value, key }">
      <div class="font-semibold text-l">
        <template v-if="key === 'active'">
          {{ value ? 'Yes' : '' }}
        </template>
        <template v-else-if="formatField(key, value, item)">
          {{ formatter.format(value) }}
        </template>
        <template v-else>
          {{ value }}
        </template>

        <template v-if="hasUrl(key, value, item) && item['url']">
          <AccountUrlLink :url="item['url']" />
        </template>
      </div>
    </template>

    <template #hidden_row="{collapse, item}">
      <AccountEditForm
        :account="item"
        :account-types="accountTypes"
        @cancel="collapse"
      />
    </template>
  </ExpandableTable>
</template>
