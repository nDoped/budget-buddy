<script setup>
  import {
    ref,
    toRaw,
    watch
  } from 'vue';
  import { useForm } from '@inertiajs/vue3'
  import ConfirmationModal from '@/Components/ConfirmationModal.vue';
  import PrimaryButton from '@/Components/PrimaryButton.vue';
  import SecondaryButton from '@/Components/SecondaryButton.vue';
  import DangerButton from '@/Components/DangerButton.vue';
  import TransactionCategory from '@/Components/TransactionCategory.vue';
  import TransactionFormBaseFields from '@/Components/TransactionFormBaseFields.vue';
  import TransactionFiles from '@/Components/TransactionFiles.vue';
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
    },
    categoryTypes: {
      type: Array,
      default: () => []
    }
  });

  const transBeingDeleted = ref(null);

  const confirmTransactionDeletion = () => {
    transBeingDeleted.value = props.transaction.id;
  };

  const transCatCounter = ref(0);
  const success = (deleted, transactionsUpdatedCount = 0) => {
    transBeingDeleted.value = null;
    transCatCounter.value++;
    editThisTransOnly.value = false;
    editAllFutureRecurring.value = false;
    let toastMsg = "Transaction Update!";
    if (deleted) {
      toastMsg = "Transaction Deleted!";
    } else if (transactionsUpdatedCount > 1) {
      toastMsg = `${transactionsUpdatedCount} Transactions Updated!`;
    }
    toast.success(toastMsg);
    emit('success');
  };

  const deleteTransactionForm = useForm({
    id:props.transaction.id,
    delete_child_transactions:false
  });
  const deleteChildTransactions = () => {
    deleteTransactionForm.delete_child_transactions = true;
    deleteTransaction();
  };
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
    edit_child_transactions: false,
    account_id: props.transaction.account_id,
    note: props.transaction.note,
    bank_identifier: props.transaction.bank_identifier,
    categories: props.transaction.categories,
    new_images: [],
    existing_images: props.transaction.existing_images,
    uploaded_file: null
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
      form.existing_images = props.transaction.existing_images;
      form.new_images = [];
      form.categories = props.transaction.categories;
      form.uploaded_file = null;
    }
  );

  const showRecurringEditDialogue = ref(false);
  const editThisTransOnly = ref(false);
  const setEditThisTransOnly = () => {
    editThisTransOnly.value = true;
    submit();
  };

  const editAllFutureRecurring = ref(false);
  const setEditAllFutureRecurring = () => {
    editAllFutureRecurring.value = true;
    submit();

  };
  function submit() {
    if (categoriesInvalid.value === true) {
      toast.error("Transaction category percentages must sum to 100%", {
        autoClose: 3000,
      });
      return;
    }

    if (props.transaction.parent_id && ! editThisTransOnly.value && ! editAllFutureRecurring.value) {
      showRecurringEditDialogue.value = true;
      return;

    } else if (props.transaction.parent_id && editThisTransOnly.value) {
      form.edit_child_transactions = false;

    } else if (props.transaction.parent_id) {
      form.edit_child_transactions = true;
    }
    showRecurringEditDialogue.value = false;

    form.post(route('transactions.update', { transaction: props.transaction.id }), {
      preserveScroll: true,
      onSuccess: (data) => success(false, data.props.data.transactions_updated_count),
      onError: (err) =>  {
        transCatCounter.value++;
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

  let ogCats = structuredClone(toRaw(props.categories));
  const cancel = () => {

    // @todo: figure out how to properly set form.isDirty when transaction
    // categories are updated
    // force TransactionCategory to reset incase any data changes were made
    form.categories = structuredClone(ogCats);
    transCatCounter.value++;
    form.reset();

    emit('cancel');
  };
</script>

<template>
  <div class="py-2">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-slate-500 overflow-x-auto border-gray-200 shadow-sm sm:rounded-lg">
        <form
          @submit.prevent="submit"
          :key="transaction.id"
        >
          <p
            v-if="form.isDirty"
            class="text-red-600 dark:text-red-400 bg-slate-500 mt-4 ml-4"
          >
            You have unsaved changes
          </p>
          <TransactionFormBaseFields
            v-model:amount="form.amount"
            v-model:transaction-date="form.transaction_date"
            v-model:credit="form.credit"
            v-model:account-id="form.account_id"
            v-model:note="form.note"
            v-model:bank-identifier="form.bank_identifier"
            :accounts="accounts"
            :errors="form.errors"
          />

          <!-- categories -->
          <div class="pt-4 pb-4 bg-slate-500 border-t border-gray-200">
            <TransactionCategory
              :total-amount="form.amount"
              :categories="props.transaction.categories"
              :category-types="categoryTypes"
              :available-categories="props.categories"
              :key="transCatCounter"
              @category-update="updateCategories"
              @invalid-category-state="setCategoriesInvalid"
            />
          </div>

          <div class="pt-4 pb-4 bg-slate-500 border-t border-gray-200">
            <TransactionFiles
              v-model:new-images="form.new_images"
              v-model:existing-images="form.existing_images"
              v-model:uploaded-file="form.uploaded_file"
            />
          </div>

          <div
            v-if="transaction.buddy_id"
            class="m-2 text-red-600 dark:text-red-400 bg-slate-500"
          >
            Edits to this transaction will also be applied to its buddy transaction
          </div>
          <div class="flex flex-wrap p-2 bg-slate-500 border-t border-gray-200">
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
                :show="! transaction.parent_id && transBeingDeleted != null"
                @close="transBeingDeleted = null"
              >
                <template #title>
                  Delete Transaction
                </template>

                <template #content>
                  You sure you wanna delete this mofo?
                </template>

                <template #footer>
                  <div class="sm:flex sm:items-start p-1 ">
                    <div class="m-2">
                      <DangerButton
                        class="ml-3"
                        :class="{ 'opacity-25': deleteTransactionForm.processing }"
                        :disabled="deleteTransactionForm.processing"
                        @click="deleteTransaction"
                      >
                        Delete
                      </DangerButton>
                    </div>

                    <div class="m-2">
                      <SecondaryButton @click="transBeingDeleted = null">
                        Cancel
                      </SecondaryButton>
                    </div>
                  </div>
                </template>
              </ConfirmationModal>

              <ConfirmationModal
                :show="transaction.parent_id && transBeingDeleted != null"
                @close="transBeingDeleted = null"
              >
                <template #title>
                  Delete Transaction
                </template>

                <template #content>
                  You are deleting a recurring transaction. Would you like to delete just this transaction or this transaction and all future transactions as well?
                </template>

                <template #footer>
                  <div class="sm:flex sm:items-start p-1 ">
                    <div class="m-2">
                      <DangerButton
                        class="ml-3"
                        :class="{ 'opacity-25': deleteTransactionForm.processing }"
                        :disabled="deleteTransactionForm.processing"
                        @click="deleteTransaction"
                      >
                        This transaction only
                      </DangerButton>
                    </div>

                    <div class="m-2">
                      <DangerButton
                        class="ml-3"
                        :class="{ 'opacity-25': deleteTransactionForm.processing }"
                        :disabled="deleteTransactionForm.processing"
                        @click="deleteChildTransactions"
                      >
                        This and all future transactions
                      </DangerButton>
                    </div>

                    <div class="m-2">
                      <SecondaryButton
                        class="ml-3"
                        @click="transBeingDeleted = null"
                      >
                        Cancel
                      </SecondaryButton>
                    </div>
                  </div>
                </template>
              </ConfirmationModal>

              <ConfirmationModal
                :show="showRecurringEditDialogue"
                @close="showRecurringEditDialogue = false"
              >
                <template #title>
                  Editing a Recurring Transaction
                </template>

                <template #content>
                  This is a recurring transaction. Would you like to apply these edits to this transaction only or to this and all future transactions?
                </template>

                <template #footer>
                  <div class="sm:flex sm:items-start p-1 ">
                    <div class="m-2">
                      <PrimaryButton
                        class="ml-3"
                        type="button"
                        @click="setEditThisTransOnly()"
                        :class="{ 'opacity-25': deleteTransactionForm.processing || form.processing }"
                        :disabled="deleteTransactionForm.processing || form.processing"
                      >
                        This transaction only
                      </PrimaryButton>
                    </div>

                    <div class="m-2">
                      <PrimaryButton
                        class="ml-3"
                        type="button"
                        @click="setEditAllFutureRecurring()"
                        :class="{ 'opacity-25': deleteTransactionForm.processing || form.processing }"
                        :disabled="deleteTransactionForm.processing || form.processing"
                      >
                        This and all future transactions
                      </PrimaryButton>
                    </div>

                    <div class="m-2">
                      <SecondaryButton
                        class="ml-3"
                        :class="{ 'opacity-25': deleteTransactionForm.processing || form.processing }"
                        @click="showRecurringEditDialogue = false"
                      >
                        Cancel
                      </SecondaryButton>
                    </div>
                  </div>
                </template>
              </ConfirmationModal>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
