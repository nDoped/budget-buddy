<script setup>
import { useForm } from '@inertiajs/vue3'
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import Checkbox from '@/Components/Checkbox.vue';

function submit() {
  form.transform((data) => ({
    ...data,
    transStart: props.startDate,
    transEnd: props.endDate
  }))
  .post(route('transactions.store'), {
    preserveScroll: true,
    onSuccess: () => form.reset(),
  });
}

let props = defineProps({
  accounts: Array,
  startDate: String,
  endDate: String
});

const form = useForm({
  transaction_date: (new Date()).toISOString().slice(0, 10),
  amount: 0,
  credit: false,
  account: '',
  note: null,
  end_date: null,
  recurring: false,
  transBuddy: false,
  transBuddyAccount: '',
  transBuddyNote: null,
  frequency: "monthly",
  bank_identifier: null,
  transStart: props.startDate,
  transEnd: props.endDate
});
</script>

<template>
  <div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-slate-500 overflow-hidden shadow-sm sm:rounded-lg">
        <form @submit.prevent="submit">
          <div class="flex flex-wrap p-6 bg-slate-500 border-b border-gray-200">
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
                <option value=true>Credit</option>
                <option selected="selected" value=false>Debit</option>
              </select>
              <InputError :message="form.errors.credit" class="mt-2" />
            </div>

            <div class="m-4">
              <InputLabel for="account" value="Account" />
              <select
                id="account"
                v-model="form.account"
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

          <div class="flex flex-wrap p-6 bg-slate-500 border-b border-gray-200">
            <div class="mb-6">
              <InputLabel for="recurring" value="Make this a recurring transaction?"/>
              <Checkbox id="recurring" v-model:checked="form.recurring" name="recurring"/>
              <InputError class="mt-2" :message="form.errors.recurring" />
            </div>

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

              </div>

              <div class="m-4">
                <InputLabel for="end_date" value="End Date" />
                <input
                  id="end_date"
                  type="date"
                  v-model="form.end_date"
                  class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                  placeholder=""
                />
                <InputError :message="form.errors.end_date" class="mt-2" />
              </div>
            </template>
          </div>

          <div class="flex flex-wrap p-6 bg-slate-500 border-b border-gray-200">
            <div class="mb-6">
              <InputLabel for="trans_buddy" value="Create a transaction buddy?"/>
              <Checkbox id="trans_buddy" v-model:checked="form.transBuddy" name="trans_buddy"/>
              <InputError class="mt-2" :message="form.errors.transBuddy" />
            </div>

            <template v-if="form.transBuddy">
              <div class="m-4">
                <InputLabel for="transBuddyAccount" value="Account" />
                <select
                  id="transBuddyAccount"
                  v-model="form.transBuddyAccount"
                  class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"

                >
                  <option value="" selected disabled hidden>Select Account...</option>
                  <option v-for="account in accounts" :value="account.id">
                    {{ account.name }}
                  </option>
                </select>
                <InputError :message="form.errors.account" class="mt-2" />
              </div>

              <div class="m-4">
                <InputLabel for="trans_buddy_note" value="Note" />
                <TextInput
                    id="trans_buddy_note"
                    v-model="form.transBuddyNote"
                    type="text"
                    class="mt-1 block w-full"
                    autofocus
                    autocomplete="note"
                />
                <InputError :message="form.errors.transBuddyNote" class="mt-2" />
              </div>

            </template>
          </div>

          <div class="m-5">
            <button
              type="submit"
              class="text-white bg-gray-600  focus:ring-4 focus:outline-none font-medium rounded-lg text-sm w-full sm:w-auto px-5 py-2.5 text-center "
              :disabled="form.processing"
              :class="{ 'opacity-25': form.processing }"
            >
              Add Transaction
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
