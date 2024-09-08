<script setup>
  import {
    ref,
    toRaw,
    watch
  } from 'vue';
  import InputLabel from '@/Components/InputLabel.vue';
  import InputDate from '@/Components/InputDate.vue';
  import InputError from '@/Components/InputError.vue';
  //import SectionBorder from '@/Components/SectionBorder.vue';
  import TextArea from '@/Components/TextArea.vue';
  import { forceNumericalInput } from '@/lib.js';

  let props = defineProps({
    errors: {
      type: Object,
      default: () => {}
    },
    accounts: {
      type: Array,
      default: () => {}
    }
  });
  const model = defineModel({
    type: Object,
    default: () => {
      return {
        transaction_date: '',
        amount: '',
        credit: false,
        account_id: '',
        note: '',
        bank_identifier: '',
      };
    }
  });
</script>

<template>
  <div class="py-2">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-slate-500 overflow-x-auto shadow-sm sm:rounded-lg">
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
                v-model="model.transaction_date"
              />
              <InputError
                :message="errors.transaction_date"
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
                v-model="model.amount"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                @keypress="forceNumericalInput($event)"
              >
              <InputError
                :message="errors.amount"
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
                v-model="model.credit"
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
                :message="errors.credit"
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
                v-model="model.account_id"
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
                :message="errors.account_id"
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
                v-model="model.note"
                class="mt-1 block w-full"
                autofocus
                autocomplete="note"
              />
              <InputError
                :message="errors.note"
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
                v-model="model.bank_identifier"
                type="text"
                class="mt-1 block w-full"
                autofocus
                autocomplete="bank_ident"
              />
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
