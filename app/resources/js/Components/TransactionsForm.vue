<script setup>
  import {
    ref
  } from 'vue';
  import { useForm } from '@inertiajs/vue3'
  import InputLabel from '@/Components/InputLabel.vue';
  import InputDate from '@/Components/InputDate.vue';
  import InputError from '@/Components/InputError.vue';
  import PrimaryButton from '@/Components/PrimaryButton.vue';
  import TransactionCategory from '@/Components/TransactionCategory.vue';
  import TransactionFormBaseFields from '@/Components/TransactionFormBaseFields.vue';
  import TextArea from '@/Components/TextArea.vue';
  import Checkbox from '@/Components/Checkbox.vue';
  import { toast } from 'vue3-toastify';
  import 'vue3-toastify/dist/index.css';

  const transCatCounter = ref(0);
  function submit() {
    if (categoriesInvalid.value === true) {
      toast.error("Transaction category percentages must sum to 100%", {
        autoClose: 3000,
      });
      return;
    }

    /* global route */
    form.post(route('transactions.store'), {
      preserveScroll: true,
      onSuccess: (data) => {
        transCatCounter.value++;
        form.reset();
        let toastMsg = 'Transaction Created!';
        if (data.props.data.transactions_created_count > 1) {
          toastMsg = `${data.props.data.transactions_created_count} Transactions Created!`;
        }
        toast.success(toastMsg);
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

  defineProps({
    accounts: {
      type: Array,
      default: () => []
    },
    categories: {
      type: Array,
      default: () => []
    },
    categoryTypes: {
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
    transaction_date: new Date((new Date()).toLocaleDateString()).toISOString().slice(0,10),
    amount: null,
    credit: false,
    account_id: '',
    note: null,
    bank_identifier: null,
    categories: [],
    recurring_end_date: null,
    recurring: false,
    trans_buddy: false,
    trans_buddy_account: '',
    trans_buddy_note: null,
    frequency: "monthly",
  });

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
</script>

<template>
  <div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-slate-500 overflow-hidden shadow-sm sm:rounded-lg">
        <form @submit.prevent="submit">
          <TransactionFormBaseFields
            v-model="form"
            :accounts="accounts"
            :errors="form.errors"
          />
          <div class="m-4 bg-slate-500">
            <TransactionCategory
              :available-categories="categories"
              :total-amount="form.amount"
              :category-types="categoryTypes"
              :key="transCatCounter"
              :errors="form.errors"
              @category-update="updateCategories"
              @invalid-category-state="setCategoriesInvalid"
            />
          </div>

          <div class="pt-4 pb-4 flex flex-wrap bg-slate-500 border-t border-b border-gray-200">
            <div class="ml-6">
              <div class="flex flex-col sm:flex-row">
                <InputLabel
                  for="recurring"
                  value="Make this a recurring transaction?"
                  class="mb-1 sm:mr-2 sm:mb-0"
                />
                <Checkbox
                  id="recurring"
                  v-model:checked="form.recurring"
                  name="recurring"
                />
                <InputError
                  class="mt-2"
                  :message="form.errors.recurring"
                />
              </div>

              <div class="flex flex-col sm:flex-row sm:mt-2">
                <template v-if="form.recurring">
                  <div class="">
                    <InputLabel
                      for="frequency"
                      value="Frequency"
                    />
                    <select
                      required
                      id="frequency"
                      v-model="form.frequency"
                      class="mt-1 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                    >
                      <option
                        value=""
                        selected
                        disabled
                        hidden
                      >
                        Select Frequency...
                      </option>

                      <option value="yearly">
                        Yearly
                      </option>

                      <option value="quarterly">
                        Quarterly
                      </option>

                      <option value="monthly">
                        Monthly
                      </option>

                      <option value="biweekly">
                        Bi-Weekly
                      </option>
                    </select>

                    <InputError
                      :message="form.errors.frequency"
                      class="mt-2"
                    />
                  </div>

                  <div class="pl-0 pr-0 sm:pl-4 sm:pr-4">
                    <InputLabel
                      for="recurring_end_date"
                      value="End Date"
                    />
                    <InputDate
                      id="date"
                      class="mt-1"
                      v-model="form.recurring_end_date"
                    />
                    <InputError
                      :message="form.errors.recurring_end_date"
                      class="mt-2"
                    />
                  </div>
                </template>
              </div>
            </div>

            <div class="ml-6">
              <div class="flex flex-col sm:flex-row">
                <InputLabel
                  for="trans_buddy"
                  value="Create a transaction buddy?"
                  class="mb-1 sm:mr-2 sm:mb-0"
                />
                <Checkbox
                  id="trans_buddy"
                  v-model:checked="form.trans_buddy"
                  name="trans_buddy"
                />
                <InputError
                  class="mt-2"
                  :message="form.errors.trans_buddy"
                />
              </div>

              <div class="flex flex-col sm:flex-row sm:mt-2">
                <template v-if="form.trans_buddy">
                  <div>
                    <InputLabel
                      for="trans_buddy_account"
                      value="Buddy Account"
                    />
                    <select
                      id="trans_buddy_account"

                      v-model="form.trans_buddy_account"
                      class="mt-1 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                    >
                      <option
                        value=""
                        selected
                        disabled
                        hidden
                      >
                        Select a Buddy...
                      </option>
                      <option
                        v-for="account in accounts"
                        :key="account + ':' + account.id"
                        :value="account.id"
                      >
                        {{ account.name }}
                      </option>
                    </select>
                    <InputError
                      :message="form.errors.trans_buddy_account"
                      class="mt-2"
                    />
                  </div>

                  <div class="pl-0 pr-0 sm:pl-4 sm:pr-4">
                    <InputLabel
                      for="trans_buddy_note"
                      value="Buddy Note"
                    />
                    <TextArea
                      id="trans_buddy_note"
                      v-model="form.trans_buddy_note"
                      type="text"
                      class="mt-1 block w-full"
                      autocomplete="note"
                    />
                    <InputError
                      :message="form.errors.trans_buddy_note"
                      class="mt-2"
                    />
                  </div>
                </template>
              </div>
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
