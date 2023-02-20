<script setup>
  import { ref, watch, onMounted } from 'vue';
  import { useForm } from '@inertiajs/vue3'
  import InputLabel from '@/Components/InputLabel.vue';
  import InputError from '@/Components/InputError.vue';
  import ConfirmationModal from '@/Components/ConfirmationModal.vue';
  import PrimaryButton from '@/Components/PrimaryButton.vue';
  import SecondaryButton from '@/Components/SecondaryButton.vue';
  import DangerButton from '@/Components/DangerButton.vue';
  import TextInput from '@/Components/TextInput.vue';
  import Checkbox from '@/Components/Checkbox.vue';

  const emit = defineEmits(['success']);

  watch(() => props.transaction, (trans) => {
    form.transaction_date = props.transaction.transaction_date;
    form.amount = props.transaction.amount;
    form.credit = props.transaction.asset;
    form.account_id = props.transaction.account_id;
    form.note = props.transaction.note;
    deleteTransactionForm.id = props.transaction.id;
    form.bank_identifier = props.transaction.bank_identifier;
  });

  let props = defineProps({
    accounts: Array,
    transaction: Object,
  });

  const transBeingDeleted = ref(null);

  const confirmTransactionDeletion = () => {
    transBeingDeleted.value = props.transaction.id;
  };

  const success = () => {
    transBeingDeleted.value = null;
    emit('success');
  };

  const deleteTransactionForm = useForm({
      id:props.transaction.id
  });
  const deleteTransaction = () => {
      deleteTransactionForm.delete(route('transactions.destroy', {id: transBeingDeleted.value}), {
        preserveScroll: true,
        onSuccess: () => success(),
        onError: (err) =>  console.error(err)
      });
  }

  const form = useForm({
    transaction_date: props.transaction.transaction_date,
    amount: props.transaction.amount,
    credit: props.transaction.asset,
    account_id: props.transaction.account_id,
    note: props.transaction.note,
    bank_identifier: props.transaction.bank_identifier
  });

  function submit() {
    form.post(route('transactions.update', { transaction: props.transaction.id }), {
      preserveScroll: true,
      onSuccess: () => success(),
      onError: (err) =>  console.error(err)
    });
  }

  /*
  onMounted(() => {
    console.log('on mount',props.transaction);
    console.log('on mount', props.transaction.transaction_date);
  });
   */

</script>

<template>
  <div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-slate-500 overflow-hidden shadow-sm sm:rounded-lg">
        <form @submit.prevent="submit" :key="transaction.id">
          <div class="flex flex-wrap p-6 bg-slate-500 border-b border-gray-200">
            <div class="m-4">
              <p>{{transaction.id}}</p>
            </div>
            <div class="m-4">
              <InputLabel for="date" value="Transaction Date" />
              <input
                id="date"
                type="date"
                v-model="form.transaction_date"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                placeholder=""

              />
              <InputError :message="form.errors.transaction_date" class="mt-2" />
            </div>

            <div class="m-4">
              <InputLabel for="amount" value="Amount" />
              <input
                type="number"
                min="0"
                step="any"
                v-model="form.amount"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"

              />
              <InputError :message="form.errors.amount" class="mt-2" />
            </div>

            <div class="m-4">
              <InputLabel for="credit" value="Credit/Debit" />
              <select
                id="credit"
                v-model="form.credit"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"

              >
                <option :value="true">Credit</option>
                <option selected="selected" :value="false">Debit</option>
              </select>
              <InputError :message="form.errors.credit" class="mt-2" />
            </div>

            <div class="m-4">
              <InputLabel for="account" value="Account" />
              <select
                id="account"
                v-model="form.account_id"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"

              >
                <option value="" selected disabled hidden>Select Account...</option>
                <option v-for="account in accounts" :value="account.id">
                  {{ account.name }}
                </option>
              </select>
              <InputError :message="form.errors.account" class="mt-2" />
            </div>

            <div class="m-4 w-full">
              <InputLabel for="bank_ident" value="Bank Identifier" />
              <TextInput
                  id="bank_ident"
                  v-model="form.bank_identifier"
                  type="text"
                  class="mt-1 block w-full"
                  autofocus
                  autocomplete="bank_ident"
              />
              <InputError :message="form.errors.bank_identifier" class="mt-2" />
            </div>

            <div class="m-4 w-full">
              <InputLabel for="note" value="Note" />
              <TextInput
                  id="note"
                  v-model="form.note"
                  type="text"
                  class="mt-1 block w-full"
                  autofocus
                  autocomplete="note"
              />
              <InputError :message="form.errors.note" class="mt-2" />
            </div>
          </div>

          <div class="flex flex-wrap p-6 bg-slate-500 border-gray-200">
            <PrimaryButton
              class="ml-3"
              type="submit"
              :class="{ 'opacity-25': deleteTransactionForm.processing || form.processing }"
              :disabled="deleteTransactionForm.processing || form.processing"
            >
              Save
            </PrimaryButton>

            <DangerButton
              class="ml-3"
              :class="{ 'opacity-25': deleteTransactionForm.processing || form.processing }"
              :disabled="deleteTransactionForm.processing || form.processing"
              @click="confirmTransactionDeletion"
            >
              Delete
            </DangerButton>
            <ConfirmationModal :show="transBeingDeleted != null" @close="transBeingDeleted = null">
              <template #title>
                Delete Transaction
              </template>

              <template #content>
                You sure you wanna delete this mofo?
              </template>

              <template #footer>
                <SecondaryButton @click="transBeingDeleted = null">
                  Cancel
                </SecondaryButton>

                <DangerButton
                  class="ml-3"
                  :class="{ 'opacity-25': deleteTransactionForm.processing }"
                  :disabled="deleteTransactionForm.processing"
                  @click="deleteTransaction"
                >
                  Delete
                </DangerButton>
              </template>
            </ConfirmationModal>
          </div>
        </form>

      </div>
    </div>
  </div>
</template>
