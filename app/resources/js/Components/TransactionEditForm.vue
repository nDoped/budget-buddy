<script setup>
  import {
    ref,
    toRaw,
    watch
  } from 'vue';
  import { useForm } from '@inertiajs/vue3'
  import InputLabel from '@/Components/InputLabel.vue';
  import InputDate from '@/Components/InputDate.vue';
  import InputError from '@/Components/InputError.vue';
  import ConfirmationModal from '@/Components/ConfirmationModal.vue';
  import PrimaryButton from '@/Components/PrimaryButton.vue';
  import SecondaryButton from '@/Components/SecondaryButton.vue';
  //import SectionBorder from '@/Components/SectionBorder.vue';
  import DangerButton from '@/Components/DangerButton.vue';
  import TransactionCategory from '@/Components/TransactionCategory.vue';
  import TextArea from '@/Components/TextArea.vue';
  import { toast } from 'vue3-toastify';
  import 'vue3-toastify/dist/index.css';

  const emit = defineEmits(['success', 'cancel']);

  let props = defineProps({
    accounts: {
      type: Array,
      default: () => []
    },

    transaction: {
      type: Object,
      default: () => {}
    },

    categories: {
      type: Array,
      default: () => []
    }
  });

  const transBeingDeleted = ref(null);

  const confirmTransactionDeletion = () => {
    transBeingDeleted.value = props.transaction.id;
  };

  const success = (deleted) => {
    transBeingDeleted.value = null;
    toast.success((deleted) ? 'Transaction Deleted!' : 'Transaction Updated!');
    emit('success');
  };

  const deleteTransactionForm = useForm({
    id:props.transaction.id
  });
  const deleteTransaction = () => {
    /* global route */
    deleteTransactionForm.delete(route('transactions.destroy', {id: transBeingDeleted.value}), {
      preserveScroll: true,
      onSuccess: () => success(true),
      onError: (err) =>  {
        console.error(err.message)
        transBeingDeleted.value = null;
        toast.error(err.message, {
          autoClose: 6000,
        });
      }
    });
  }

  const form = useForm({
    transaction_date: props.transaction.transaction_date,
    amount: props.transaction.amount,
    credit: props.transaction.asset,
    account_id: props.transaction.account_id,
    note: props.transaction.note,
    bank_identifier: props.transaction.bank_identifier,
    categories: props.transaction.categories
  });

  watch(
    () => props.transaction,
    () => {
      form.transaction_date = props.transaction.transaction_date;
      form.amount = props.transaction.amount;
      form.credit = props.transaction.asset;
      form.account_id = props.transaction.account_id;
      form.note = props.transaction.note;
      deleteTransactionForm.id = props.transaction.id;
      form.bank_identifier = props.transaction.bank_identifier;
      form.categories = props.transaction.categories;
    }
  );


  //const showRecurringEditDialogue = ref(null);
  function submit() {
    if (categoriesInvalid.value === true) {
      toast.error("Transaction category percentages must sum to 100%", {
        autoClose: 3000,
      });
      return;
    }

    /*
    if (props.transaction.parent_id) {
      toast.error("Editing recurring", {
        autoClose: 3000,
      });
      return;
    }
     */

    form.post(route('transactions.update', { transaction: props.transaction.id }), {
      preserveScroll: true,
      onSuccess: () => success(false),
      onError: (err) =>  {
        console.error(err)
        transBeingDeleted.value = null;
        for (let field in err) {
          toast.error(err[field], {
            autoClose: 3000,
          });
        }
      }
    });
  }

  let ogCats = structuredClone(toRaw(props.categories));
  const categoriesInvalid = ref(false);
  const updateCategories = (newCats) => {
    categoriesInvalid.value = false;
    form.categories = [];
    newCats.value.forEach(c => form.categories.push(c));
  };

  const setCategoriesInvalid = () => {
    categoriesInvalid.value = true;
    form.categories = [];
  };

  const transCatCounter = ref(0);
  const cancel = () => {
    form.categories = structuredClone(ogCats);
    // force TransactionCategory to reset incase any data changes were made
    transCatCounter.value++;
    emit('cancel');
  };
</script>

<template>
  <div class="py-2">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-slate-500 overflow-x-auto shadow-sm sm:rounded-lg">
        <form
          @submit.prevent="submit"
          :key="transaction.id"
        >
          <!--
          <div class="m-2 ">
            <p class="text-lg text-semibold">
              Editting transaction {{ transaction.id }}
            </p>
          </div>
          -->
          <div class="flex flex-wrap p-1 ">
            <div class="m-2">
              <!-- date -->
              <div class="m-2">
                <InputLabel
                  for="date"
                  value="Transaction Date"
                />
                <InputDate
                  id="date"
                  v-model="form.transaction_date"
                />
                <InputError
                  :message="form.errors.transaction_date"
                  class="mt-2"
                />
              </div>

              <!-- amount -->
              <div class="m-2">
                <InputLabel
                  for="amount"
                  value="Amount"
                />
                <input
                  type="number"
                  min="0"
                  step="any"
                  v-model="form.amount"
                  class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                >
                <InputError
                  :message="form.errors.amount"
                  class="mt-2"
                />
              </div>
            </div>


            <div class="m-2">
              <!-- credit/debit -->
              <div class="m-2">
                <InputLabel
                  for="credit"
                  value="Credit/Debit"
                />
                <select
                  id="credit"
                  v-model="form.credit"
                  class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                >
                  <option :value="true">
                    Credit
                  </option>
                  <option
                    selected="selected"
                    :value="false"
                  >
                    Debit
                  </option>
                </select>
                <InputError
                  :message="form.errors.credit"
                  class="mt-2"
                />
              </div>

              <!-- accounts -->
              <div class="m-2">
                <InputLabel
                  for="account"
                  value="Account"
                />
                <select
                  id="account"
                  v-model="form.account_id"
                  class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                >
                  <option
                    value=""
                    selected
                    disabled
                    hidden
                  >
                    Select Account...
                  </option>

                  <option
                    v-for="account in accounts"
                    :key="account.id"
                    :value="account.id"
                  >
                    {{ account.name }}
                  </option>
                </select>

                <InputError
                  :message="form.errors.account"
                  class="mt-2"
                />
              </div>
            </div>

            <div class="m-2 grow">
              <div class="m-1">
                <InputLabel
                  for="note"
                  value="Note"
                />
                <TextArea
                  id="note"
                  v-model="form.note"
                  class="mt-1 block w-full"
                  autofocus
                  autocomplete="note"
                />
                <InputError
                  :message="form.errors.note"
                  class="mt-2"
                />
              </div>

              <div class="m-1">
                <InputLabel
                  for="bank_ident"
                  value="Bank Identifier"
                />
                <TextArea
                  id="bank_ident"
                  v-model="form.bank_identifier"
                  type="text"
                  class="mt-1 block w-full"
                  autofocus
                  autocomplete="bank_ident"
                />
                <InputError
                  :message="form.errors.bank_identifier"
                  class="mt-2"
                />
              </div>
            </div>
          </div>

          <!-- categories -->
          <div class="m-2 bg-slate-500 border-t border-b border-gray-200">
            <TransactionCategory
              :categories="props.transaction.categories"
              :available-categories="props.categories"
              :key="transCatCounter"
              @category-update="updateCategories"
              @invalid-category-state="setCategoriesInvalid"
            />
          </div>

          <div
            v-if="transaction.buddy_id"
            class="m-2 text-red-600 dark:text-red-400 bg-slate-500"
          >
            Edits to this transaction will also be applied to its buddy transaction
          </div>
          <div class="flex flex-wrap p-2 bg-slate-500 border-gray-200">
            <div>
              <PrimaryButton
                class="ml-3"
                type="button"
                @click="submit"
                :class="{ 'opacity-25': deleteTransactionForm.processing || form.processing }"
                :disabled="deleteTransactionForm.processing || form.processing"
              >
                Save
              </PrimaryButton>

              <SecondaryButton
                @click="cancel"
                class="ml-3"
              >
                Cancel
              </SecondaryButton>

              <DangerButton
                class="ml-3"
                :class="{ 'opacity-25': deleteTransactionForm.processing || form.processing }"
                :disabled="deleteTransactionForm.processing || form.processing"
                @click="confirmTransactionDeletion"
              >
                Delete
              </DangerButton>
              <ConfirmationModal
                :show="transBeingDeleted != null"
                @close="transBeingDeleted = null"
              >
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
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
