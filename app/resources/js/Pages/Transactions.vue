<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Transactions from '@/Components/Transactions.vue';

const props = defineProps({
    data: Object
});
</script>

<template>
  <Head title="Transactions" />



    <AppLayout title="Dashboard">

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-slate-700 overflow-hidden shadow-xl sm:rounded-lg">
                  <Transactions
                    :transactions='data.transactions'
                    :accounts='data.accounts'
                  />
                </div>
            </div>
        </div>
    </AppLayout>

</template>


