<script setup>
  import {
    ref,
    watch
  } from 'vue';
  import { useForm } from '@inertiajs/vue3'
  import ConfirmationModal from '@/Components/ConfirmationModal.vue';
  import PrimaryButton from '@/Components/PrimaryButton.vue';
  import SecondaryButton from '@/Components/SecondaryButton.vue';
  import DangerButton from '@/Components/DangerButton.vue';
  import InputError from '@/Components/InputError.vue';
  import InputLabel from '@/Components/InputLabel.vue';
  import TextInput from '@/Components/TextInput.vue';
  import Checkbox from '@/Components/Checkbox.vue';
  import { toast } from 'vue3-toastify';

  const emit = defineEmits(['success', 'cancel']);

  const props = defineProps({
    account: {
      type: Object,
      default: () => {}
    },
    accountTypes: {
      type: Object,
      default: () => {}
    }
  });

  const form = useForm({
    name: props.account.name,
    type: props.account.type_id || getTypeIdFromName(props.account.type),
    url: props.account.url,
    interest_rate: props.account.interest_rate,
    initial_balance: props.account.initial_balance,
    active: props.account.active,
  });

  const getTypeIdFromName = (typeName) => {
    if (!typeName) return null;
    const match = props.accountTypes.find(t => t.name === typeName);
    return match ? match.id : null;
  };

  watch(
    () => props.account,
    () => {
      form.name = props.account.name;
      form.type = props.account.type_id || getTypeIdFromName(props.account.type);
      form.url = props.account.url;
      form.interest_rate = props.account.interest_rate;
      form.initial_balance = props.account.initial_balance;
      form.active = props.account.active;
      deleteAccountForm.id = props.account.id;
    }
  );

  const cancel = () => {
    emit('cancel');
  };

  const accountBeingDeleted = ref(null);

  const confirmAccountDeletion = () => {
    accountBeingDeleted.value = props.account.id;
  };

  const success = (deleting) => {
    accountBeingDeleted.value = null;
    toast.success((deleting) ? 'Account Deleted!' : 'Account Updated!');
  };

  const deleteAccountForm = useForm({
    id: props.account.id
  });

  const deleteAccount = () => {
    deleteAccountForm.delete(route('accounts.destroy', { id: accountBeingDeleted.value }), {
      preserveScroll: true,
      onSuccess: () => success(true),
      onError: (err) => {
        console.error(err.message);
        accountBeingDeleted.value = null;
        toast.error(err.message, {
          autoClose: 6000,
        });
      }
    });
  };

  function submit() {
    form.patch(route('accounts.update', { account: props.account.id }), {
      preserveScroll: true,
      onSuccess: () => success(false),
      onError: (err) => {
        console.error(err.message);
        accountBeingDeleted.value = null;
        for (let field in err) {
          toast.error(err[field], {
            autoClose: 3000,
          });
        }
      }
    });
  }
</script>

<template>
  <div class="py-2">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-slate-500 overflow-hidden shadow-sm sm:rounded-lg">
        <form
          @submit.prevent="submit"
          :key="account.id"
        >
          <div class="flex flex-wrap p-6 bg-slate-500 border-b border-gray-200">
            <div class="m-4">
              <InputLabel
                for="edit-type"
                value="Account Type"
              />
              <select
                :id="'edit-type-' + account.id"
                v-model="form.type"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
              >
                <option value="">
                  Select Type...
                </option>
                <option
                  v-for="(type, i) in accountTypes"
                  :key="i"
                  :value="type.id"
                >
                  {{ type.name }}
                </option>
              </select>
              <InputError
                :message="form.errors.type"
                class="mt-2"
              />
            </div>

            <div class="m-4">
              <InputLabel
                :for="'edit-name-' + account.id"
                value="Account Name"
              />
              <TextInput
                :id="'edit-name-' + account.id"
                v-model="form.name"
                type="text"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                required
              />
              <InputError
                :message="form.errors.name"
                class="mt-2"
              />
            </div>

            <div class="m-4">
              <InputLabel
                :for="'edit-bal-' + account.id"
                value="Initial Balance"
              />
              <TextInput
                :id="'edit-bal-' + account.id"
                v-model="form.initial_balance"
                type="text"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                required
              />
              <InputError
                :message="form.errors.initial_balance"
                class="mt-2"
              />
            </div>

            <div class="m-4">
              <InputLabel
                :for="'edit-interest-' + account.id"
                value="Interest Rate"
              />
              <TextInput
                :id="'edit-interest-' + account.id"
                v-model="form.interest_rate"
                type="text"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
              />
              <InputError
                :message="form.errors.interest_rate"
                class="mt-2"
              />
            </div>

            <div class="m-4">
              <InputLabel
                :for="'edit-url-' + account.id"
                value="URL"
              />
              <TextInput
                :id="'edit-url-' + account.id"
                v-model="form.url"
                type="text"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                placeholder="https://example.org"
              />
              <InputError
                :message="form.errors.url"
                class="mt-2"
              />
            </div>

            <div class="m-4 flex items-center">
              <Checkbox
                :id="'edit-active-' + account.id"
                v-model:checked="form.active"
                name="active"
                class="mr-2"
              />
              <InputLabel
                :for="'edit-active-' + account.id"
                value="Active"
              />
              <InputError
                :message="form.errors.active"
                class="mt-2"
              />
            </div>
          </div>

          <div class="flex flex-wrap p-3 bg-slate-500 border-gray-200">
            <PrimaryButton
              class="ml-3"
              type="submit"
              :class="{ 'opacity-25': deleteAccountForm.processing || form.processing }"
              :disabled="deleteAccountForm.processing || form.processing"
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
              :class="{ 'opacity-25': deleteAccountForm.processing || form.processing }"
              :disabled="deleteAccountForm.processing || form.processing"
              @click="confirmAccountDeletion"
            >
              Delete
            </DangerButton>
            <ConfirmationModal
              :show="accountBeingDeleted != null"
              @close="accountBeingDeleted = null"
            >
              <template #title>
                Delete Account
              </template>

              <template #content>
                You sure you wanna delete this account?
              </template>

              <template #footer>
                <SecondaryButton @click="accountBeingDeleted = null">
                  Cancel
                </SecondaryButton>

                <DangerButton
                  class="ml-3"
                  :class="{ 'opacity-25': deleteAccountForm.processing }"
                  :disabled="deleteAccountForm.processing"
                  @click="deleteAccount"
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
