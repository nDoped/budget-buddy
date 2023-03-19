<script setup>
  import { ref, watch } from 'vue';
  import { useForm } from '@inertiajs/vue3'
  import InputLabel from '@/Components/InputLabel.vue';
  import InputError from '@/Components/InputError.vue';
  import ConfirmationModal from '@/Components/ConfirmationModal.vue';
  import PrimaryButton from '@/Components/PrimaryButton.vue';
  import SecondaryButton from '@/Components/SecondaryButton.vue';
  import DangerButton from '@/Components/DangerButton.vue';
  import TextInput from '@/Components/TextInput.vue';
  import Checkbox from '@/Components/Checkbox.vue';
  import { toast } from 'vue3-toastify';
  import 'vue3-toastify/dist/index.css';

  const emit = defineEmits(['success', 'cancel']);

  watch(
    () => props.category,
    () => {
      form.name = props.category.name;
      form.color = props.category.color;
      form.extra_expense = (props.category.extra_expense) ? true : false;
      form.recurring_expense = (props.category.recurring_expense) ? true : false;
      form.housing_expense = (props.category.housing_expense) ? true : false;
      form.utility_expense = (props.category.utility_expense) ? true : false;
      form.primary_income = (props.category.primary_income) ? true : false;
      form.extra_income = (props.category.extra_income) ? true : false;
      deleteCategoryForm.id = props.category.id;
    }
  );

  const props = defineProps({
    category: {
      type: Object,
      default: () => {}
    }
  });

  const catBeingDeleted = ref(null);

  const confirmCatDeletion = () => {
    catBeingDeleted.value = props.category.id;
  };

  const success = (deleting) => {
    catBeingDeleted.value = null;
    toast.success((deleting) ? 'Category Deleted!' : 'Category Updated!');
    emit('success');
  };

  const deleteCategoryForm = useForm({
    id:props.category.id
  });

  const deleteCategory = () => {
    deleteCategoryForm.delete(route('categories.destroy', {id: catBeingDeleted.value}), {
      preserveScroll: true,
      onSuccess: () => success(true),
      onError: (err) =>  {
        console.error(err.message)
        catBeingDeleted.value = null;
        toast.error(err.message, {
          autoClose: 6000,
        });
      }
    });
  };

  const form = useForm({
    name: props.category.name,
    color: props.category.color,
    extra_expense: (props.category.extra_expense) ? true : false,
    recurring_expense: (props.category.recurring_expense) ? true : false,
    housing_expense: (props.category.housing_expense) ? true : false,
    utility_expense: (props.category.utility_expense) ? true : false,
    primary_income: (props.category.primary_income) ? true : false,
    extra_income: (props.category.extra_income) ? true : false
  });

  function submit() {
    /* global route */
    form.post(route('categories.update', { category: props.category.id }), {
      preserveScroll: true,
      onSuccess: () => success(false),
      onError: (err) =>  {
        console.error(err.message)
        catBeingDeleted.value = null;
        for (let field in err) {
          toast.error(err[field], {
            autoClose: 3000,
          });
        }
      }
    });
  }

  const uuid = crypto.randomUUID();
  const getUuid = (el, i) => {
    return `${el}-${uuid}`;
  };
</script>

<template>
  <div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-slate-500 overflow-hidden shadow-sm sm:rounded-lg">
        <form
          @submit.prevent="submit"
          :key="category.id"
        >
          <div class="flex flex-wrap p-6 bg-slate-500 border-b border-gray-200">
            <div class="m-4">
              <p>
                {{ category.id }}
              </p>
            </div>
            <div class="m-4">
              <InputLabel
                :for="getUuid('cat-name')"
                value="Name"
              />
              <TextInput
                :id="getUuid('cat-name')"
                v-model="form.name"
                type="text"
                class="mt-1 block w-full"
                autofocus
                autocomplete="bank_ident"
              />
              <InputError
                :message="form.errors.name"
                class="mt-2"
              />
            </div>

            <div class="m-4">
              <InputLabel
                :for="getUuid('cat-color')"
                value="color"
              />
              <input
                :id="getUuid('cat-color')"
                type="color"
                v-model="form.color"
              >
              <InputError
                :message="form.errors.color"
                class="mt-2"
              />
            </div>

            <div class="m-4">
              <InputLabel
                :for="getUuid('extra-expense')"
                value="Extra Expense?"
              />
              <Checkbox
                :id="getUuid('extra-expense')"
                v-model:checked="form.extra_expense"
                name="extra_expense"
              />
              <InputError
                class="mt-2"
                :message="form.errors.extra_expense"
              />
            </div>

            <div class="m-4">
              <InputLabel
                :for="getUuid('recurring-expense')"
                value="Recurring Expense?"
              />
              <Checkbox
                :id="getUuid('recurring-expense')"
                v-model:checked="form.recurring_expense"
                name="recurring_expense"
              />
              <InputError
                class="mt-2"
                :message="form.errors.recurring_expense"
              />
            </div>

            <div class="m-4">
              <InputLabel
                :for="getUuid('housing-expense')"
                value="Housing Expense?"
              />
              <Checkbox
                :id="getUuid('housing-expense')"
                v-model:checked="form.housing_expense"
                name="housing_expense"
              />
              <InputError
                class="mt-2"
                :message="form.errors.housing_expense"
              />
            </div>

            <div class="m-4">
              <InputLabel
                :for="getUuid('utility-expense')"
                value="Utility?"
              />
              <Checkbox
                :id="getUuid('utility-expense')"
                v-model:checked="form.utility_expense"
                name="utility_expense"
              />
              <InputError
                class="mt-2"
                :message="form.errors.utility_expense"
              />
            </div>


            <div class="m-4">
              <InputLabel
                value="Primary Income?"
              />
              <Checkbox
                :id="getUuid('primary-income')"
                v-model:checked="form.primary_income"
                name="primary_income"
              />
              <InputError
                class="mt-2"
                :message="form.errors.primary_income"
              />
            </div>

            <div class="m-4">
              <InputLabel
                value="Extra Income?"
              />
              <Checkbox
                :id="getUuid('extra-income')"
                v-model:checked="form.extra_income"
                name="extra_income"
              />
              <InputError
                class="mt-2"
                :message="form.errors.extra_income"
              />
            </div>
          </div>

          <div class="flex flex-wrap p-6 bg-slate-500 border-gray-200">
            <PrimaryButton
              class="ml-3"
              type="submit"
              :class="{ 'opacity-25': deleteCategoryForm.processing || form.processing }"
              :disabled="deleteCategoryForm.processing || form.processing"
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
              :class="{ 'opacity-25': deleteCategoryForm.processing || form.processing }"
              :disabled="deleteCategoryForm.processing || form.processing"
              @click="confirmCatDeletion"
            >
              Delete
            </DangerButton>
            <ConfirmationModal
              :show="catBeingDeleted != null"
              @close="catBeingDeleted = null"
            >
              <template #title>
                Delete Category
              </template>

              <template #content>
                You sure you wanna delete this mofo?
              </template>

              <template #footer>
                <SecondaryButton @click="catBeingDeleted = null">
                  Cancel
                </SecondaryButton>

                <DangerButton
                  class="ml-3"
                  :class="{ 'opacity-25': deleteCategoryForm.processing }"
                  :disabled="deleteCategoryForm.processing"
                  @click="deleteCategory"
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
