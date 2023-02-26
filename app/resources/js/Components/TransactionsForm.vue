<script setup>
  import { useForm } from '@inertiajs/vue3'
  import InputLabel from '@/Components/InputLabel.vue';
  import InputDate from '@/Components/InputDate.vue';
  import InputError from '@/Components/InputError.vue';
  import PrimaryButton from '@/Components/PrimaryButton.vue';
  import TextInput from '@/Components/TextInput.vue';
  import Checkbox from '@/Components/Checkbox.vue';
  import { toast } from 'vue3-toastify';
  import 'vue3-toastify/dist/index.css';

  function submit() {
    /* global route */
    form.post(route('transactions.store'), {
      preserveScroll: true,
      onSuccess: () => {
        form.reset();
        toast.success('Transaction Created!');
      },

      onError: (err) =>  {
        console.error(err.message);
        for (let field in err) {
          toast.error(err[field], {
            autoClose: 6000,
          });
        }
      }
    });
  }

  let props = defineProps({
    accounts: {
      type: Array,
      default: () => []
    },
    startDate: {
      type: String,
      default: () => ''
    },
    endDate: {
      type: String,
      default: () => ''
    }
  });

  const form = useForm({
    transaction_date: (new Date()).toISOString().slice(0, 10),
    amount: 0,
    credit: false,
    account_id: '',
    note: null,
    bank_identifier: null,
    categories: '{ "CategoryName": 100 }',
    recurring_end_date: null,
    recurring: false,
    trans_buddy: false,
    trans_buddy_account: '',
    trans_buddy_note: null,
    frequency: "monthly",
  });
</script>

<template>
  <div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-slate-500 overflow-hidden shadow-sm sm:rounded-lg">
        <form @submit.prevent="submit">
          <div class="flex flex-wrap p-6 bg-slate-500 border-b border-gray-200">
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
                  :value="account.id"
                  :key="account.id"
                >
                  {{ account.name }}
                </option>
              </select>
              <InputError
                :message="form.errors.account_id"
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

            <div class="m-4 w-full">
              <InputLabel for="categories" value="Categories" />
              <TextInput
                  id="note"
                  v-model="form.categories"
                  type="text"
                  class="mt-1 block w-full"
                  autofocus
                  autocomplete="note"
              />
              <InputError :message="form.errors.categories" class="mt-2" />
            </div>
          </div>

          <div class="flex flex-wrap p-6 bg-slate-500 border-b border-gray-200">
            <div class="m-6">
              <InputLabel for="recurring" value="Make this a recurring transaction?"/>
              <Checkbox id="recurring" v-model:checked="form.recurring" name="recurring"/>
              <InputError class="mt-2" :message="form.errors.recurring" />

              <template v-if="form.recurring">
                <div class="m-4">
                  <InputLabel for="frequency" value="Frequency"/>
                  <select
                    required
                    id="frequency"
                    v-model="form.frequency"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"

                  >
                    <option value="" selected disabled hidden>Select Frequency...</option>
                    <option value="monthly">Monthly</option>
                    <option value="biweekly">Bi-Weekly</option>
                  </select>
                  <InputError :message="form.errors.frequency" class="mt-2" />

                </div>

                <div class="m-4">
                  <InputLabel
                    for="recurring_end_date"
                    value="End Date"
                  />
                  <InputDate
                    id="date"
                    v-model="form.recurring_end_date"
                  />
                  <InputError
                    :message="form.errors.recurring_end_date"
                    class="mt-2"
                  />
                </div>
              </template>
            </div>

            <div class="m-6">
              <InputLabel for="trans_buddy" value="Create a transaction buddy?"/>
              <Checkbox id="trans_buddy" v-model:checked="form.trans_buddy" name="trans_buddy"/>
              <InputError class="mt-2" :message="form.errors.trans_buddy" />

              <template v-if="form.trans_buddy">
                <div class="m-4">
                  <InputLabel for="trans_buddy_account" value="Buddy Account" />
                  <select
                    id="trans_buddy_account"
                    v-model="form.trans_buddy_account"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"

                  >
                    <option value="" selected disabled hidden>Select a Buddy...</option>
                    <option v-for="account in accounts" :value="account.id">
                      {{ account.name }}
                    </option>
                  </select>
                  <InputError :message="form.errors.trans_buddy_account" class="mt-2" />
                </div>

                <div class="m-4">
                  <InputLabel for="trans_buddy_note" value="Buddy Note" />
                  <TextInput
                      id="trans_buddy_note"
                      v-model="form.trans_buddy_note"
                      type="text"
                      class="mt-1 block w-full"
                      autofocus
                      autocomplete="note"
                  />
                  <InputError :message="form.errors.trans_buddy_note" class="mt-2" />
                </div>
              </template>
            </div>
          </div>

          <div class="m-5">
            <PrimaryButton
              class="ml-3"
              :class="{ 'opacity-25': form.processing }"
              :disabled="form.processing"
              type="submit"
            >
              Add Transaction
            </PrimaryButton>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
