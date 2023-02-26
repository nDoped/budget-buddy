<script setup>
  import { ref, watch } from 'vue';
  import { useForm } from '@inertiajs/vue3'
  import InputLabel from '@/Components/InputLabel.vue';
  import InputDate from '@/Components/InputDate.vue';
  import InputError from '@/Components/InputError.vue';
  import ConfirmationModal from '@/Components/ConfirmationModal.vue';
  import PrimaryButton from '@/Components/PrimaryButton.vue';
  import SecondaryButton from '@/Components/SecondaryButton.vue';
  import DangerButton from '@/Components/DangerButton.vue';
  import TextArea from '@/Components/TextArea.vue';
  import TextInput from '@/Components/TextInput.vue';
  import { toast } from 'vue3-toastify';
  import 'vue3-toastify/dist/index.css';

  const emit = defineEmits(['success', 'cancel']);

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
      catsJsonStr.value = JSON.stringify(props.transaction.categories);
      form.categories = catsJsonStr.value;
    }
  );

  let props = defineProps({
    accounts: {
      type: Array,
      default: () => []
    },

    transaction: {
      type: Object,
      default: () => {}
    }
  });

  const transBeingDeleted = ref(null);

  const confirmTransactionDeletion = () => {
    transBeingDeleted.value = props.transaction.id;
  };

  const catsJsonStr = ref(JSON.stringify(props.transaction.categories));
  const success = (created) => {
    transBeingDeleted.value = null;
    toast.success((created) ? 'Transaction Updated!' : 'Transaction Deleted!');
    emit('success');
  };

  const deleteTransactionForm = useForm({
    id:props.transaction.id
  });
  const deleteTransaction = () => {
    /* global route */
    deleteTransactionForm.delete(route('transactions.destroy', {id: transBeingDeleted.value}), {
      preserveScroll: true,
      onSuccess: () => success(false),
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
    categories: catsJsonStr.value
  });

  function submit() {
    form.post(route('transactions.update', { transaction: props.transaction.id }), {
      preserveScroll: true,
      onSuccess: () => success(true),
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

  /*
  onMounted(() => {
    console.log('on mount',props.transaction);
    console.log('on mount', props.transaction.categories);
  });
  */
</script>

<template>
  <div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-slate-500 overflow-hidden shadow-sm sm:rounded-lg">
        <form
          @submit.prevent="submit"
          :key="transaction.id"
        >
          <div class="flex flex-wrap p-6 bg-slate-500 border-b border-gray-200">
            <div class="m-4">
              <p>{{ transaction.id }}</p>
            </div>
            <div class="m-4">
              <!-- date -->
              <div class="m-4">
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
              <div class="m-4">
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


            <div class="m-4">
              <!-- credit/debit -->
              <div class="m-4">
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
              <div class="m-4">
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

            <!-- categories -->
            <div class="m-4 flex-grow">
              <div class="m-4 w-full">
                <InputLabel
                  for="cats"
                  value="Categories"
                />
                <TextArea
                  id="cats"
                  v-model="form.categories"
                  type="text"
                  class="mt-1 block w-full"
                  autofocus
                  autocomplete="cat"
                />
                <InputError
                  :message="form.errors.categories"
                  class="mt-2"
                />
              </div>

              <template
                v-for="(percentage, category, i) in transaction.categories"
                :key="i"
              >
                <div class="m-4 w-full">
                  {{ category + ' :: ' + percentage }}
                  <!--
                  <InputLabel
                    for="cat"
                    value="Category"
                  />
                  <TextInput
                    id="cat"
                    v-model="form.categories[category]"
                    type="text"
                    class="mt-1 block w-full"
                    autofocus
                    autocomplete="cat"
                  />
                  <InputError
                    :message="form.errors.categories"
                    class="mt-2"
                  />

                  <InputLabel
                    for="percent"
                    value="Percentage"
                  />
                  <TextInput
                    id="percent"
                    v-model="form.categories[category].percentage"
                    type="text"
                    class="mt-1 block w-full"
                    autofocus
                    autocomplete="cat"
                  />
                  <InputError
                    :message="form.errors.categories"
                    class="mt-2"
                  />
                  -->
                </div>
              </template>
            </div>

            <div class="m-4 w-full">
              <InputLabel
                for="bank_ident"
                value="Bank Identifier"
              />
              <TextInput
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

            <div class="m-4 w-full">
              <InputLabel
                for="note"
                value="Note"
              />
              <TextInput
                id="note"
                v-model="form.note"
                type="text"
                class="mt-1 block w-full"
                autofocus
                autocomplete="note"
              />
              <InputError
                :message="form.errors.note"
                class="mt-2"
              />
            </div>
          </div>

          <div class="flex flex-wrap p-6 bg-slate-500 border-gray-200">
            <div>
              <PrimaryButton
                class="ml-3"
                type="submit"
                :class="{ 'opacity-25': deleteTransactionForm.processing || form.processing }"
                :disabled="deleteTransactionForm.processing || form.processing"
              >
                Save
              </PrimaryButton>

              <SecondaryButton
                @click="$emit('cancel')"
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
