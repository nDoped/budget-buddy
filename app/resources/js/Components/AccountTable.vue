<script setup>
  import { ref, inject } from 'vue';
  import { toast } from 'vue3-toastify';
  import { useForm } from '@inertiajs/vue3'
  import ExpandableTable from '@/Components/ExpandableTable.vue';
  import AccountUrlLink from '@/Components/AccountUrlLink.vue';
  const formatter = inject('formatter');

  defineProps({
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
    //{ key: 'owner', label: 'Owner' },
    { key: 'interest_rate', label: 'Interest Rate' },
    { key: 'initial_balance', label: 'Initial Balance', sortable:true, format:true },
    { key: 'url', label: 'URL' }
  ]);

  const success = () => {
    toast.success('Account Created!');
    form.reset();
  };

  const form = useForm({
    name: null,
    type: null,
    url: null,
    interest_rate: null,
    initial_balance: null,
  });
  function submit() {
    /* global route */
    form.post(route('accounts.store'), {
      preserveScroll: true,
      onSuccess: success,
    });
  }
</script>

<template>
  <ExpandableTable
    class="grow w-full bg-gray-800 text-slate-300"
    :items="accounts"
    :fields="fields"
    :expand="false"
    :pagination-start="100"
  >
    <template #visible_row="{ item , value, key }">
      <div class="font-semibold text-l">
        <template v-if="formatField(key, value, item)">
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
  </ExpandableTable>
</template>
