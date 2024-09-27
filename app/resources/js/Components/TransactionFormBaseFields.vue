<script setup>
  import {
    ref,
    onMounted
  } from 'vue';
  import InputLabel from '@/Components/InputLabel.vue';
  import InputDate from '@/Components/InputDate.vue';
  import InputError from '@/Components/InputError.vue';
  import TextArea from '@/Components/TextArea.vue';
  import CurrencyInput from '@/Components/CurrencyInput.vue';

  /*
   * Models
   */
  const transactionDate = defineModel('transactionDate', {
    type: String,
    default: ""
  });
  const amount = defineModel('amount', {
    type: String,
    default: ""
  });
  const credit = defineModel('credit', {
    type: Boolean,
    default: true
  });
  const accountId = defineModel('accountId', {
    type: String,
    default: ""
  });
  const note = defineModel('note', {
    type: String,
    default: ""
  });
  const bankIdentifier = defineModel('bankIdentifier', {
    type: String,
    default: ""
  });

  defineProps({
    errors: {
      type: Object,
      default: () => {}
    },
    accounts: {
      type: Array,
      default: () => {}
    }
  });
  const currencyInputEl = ref(null);
  onMounted(() => {
    currencyInputEl.value.focus();
  });
  const uuid = crypto.randomUUID();
  const getUuid = (el, i = 0) => {
    return `${el}-${i}-${uuid}`;
  };
</script>

<template>
  <div class="py-2">
    <!-- Base Fields -->
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-slate-500 sm:rounded-lg">
        <div class="flex flex-wrap p-1 ">
          <div class="m-2">
            <!-- date -->
            <div class="m-2">
              <InputLabel
                :for="getUuid('transaction-date')"
                value="Transaction Date"
              />
              <InputDate
                :id="getUuid('transaction-date')"
                v-model="transactionDate"
              />
              <InputError
                :message="errors.transaction_date"
                class="mt-2"
              />
            </div>

            <!-- amount -->
            <div class="m-2">
              <CurrencyInput
                ref="currencyInputEl"
                :input-id="getUuid('amount')"
                :error-message="errors.amount"
                v-model="amount"
              />
            </div>
          </div>

          <div class="m-2">
            <!-- credit/debit -->
            <div class="m-2">
              <InputLabel
                :for="getUuid('credit-select')"
                value="Credit/Debit"
              />
              <select
                :id="getUuid('credit-select')"
                v-model="credit"
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
                :for="getUuid('account-select')"
                value="Account"
              />
              <select
                :id="getUuid('account-select')"
                v-model="accountId"
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
                :for="getUuid('note')"
                value="Note"
              />
              <TextArea
                :id="getUuid('note')"
                v-model="note"
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
                :for="getUuid('bank_ident')"
                value="Bank Identifier"
              />
              <TextArea
                :id="getUuid('bank_ident')"
                v-model="bankIdentifier"
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
