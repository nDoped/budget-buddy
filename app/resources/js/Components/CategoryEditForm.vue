<script setup>
  import {
    ref,
    onMounted,
    watch
  } from 'vue';
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

  const props = defineProps({
    category: {
      type: Object,
      default: () => {}
    }
  });

  const form = useForm({
    name: props.category.name,
    color: props.category.color,
    category_type: null
  });

  const setFormCategoryType = () => {
    if (props.category.extra_expense) {
      form.category_type = 'extra_expense';

    } else if (props.category.regular_expense) {
      form.category_type = 'regular_expense';

    } else if (props.category.recurring_expense) {
      form.category_type = 'recurring_expense';

    } else if (props.category.housing_expense) {
      form.category_type = 'housing_expense';

    } else if (props.category.utility_expense) {
      form.category_type = 'utility_expense';

    } else if (props.category.primary_income) {
      form.category_type = 'primary_income';

    } else if (props.category.secondary_income) {
      form.category_type = 'secondary_income';

    } else {
      form.category_type = null;
    }
  };

  onMounted(setFormCategoryType);

  watch(
    () => props.category,
    () => {
      form.name = props.category.name;
      form.color = props.category.color;
      setFormCategoryType();
      deleteCategoryForm.id = props.category.id;
    }
  );

  const cancel = () => {
    setFormCategoryType();
    emit('cancel');
  };

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
          <div class="flex flex-col p-6 bg-slate-500 border-b border-gray-200">
            <div class="flex flex-row">
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
              </div>
            </div>

            <div class="flex flex-row">
              <div class="m-4">
                <InputLabel
                  value="Primary Income?"
                />
                <input
                  :id="getUuid('primary-income')"
                  v-model="form.category_type"
                  type="radio"
                  value="primary_income"
                >
              </div>

              <div class="m-4">
                <InputLabel
                  value="Secondary Income?"
                />
                <input
                  :id="getUuid('secondary-income')"
                  v-model="form.category_type"
                  type="radio"
                  value="secondary_income"
                >
              </div>

              <div class="m-4">
                <InputLabel
                  :for="getUuid('regular-expense')"
                  value="Regular Expense?"
                />
                <input
                  :id="getUuid('regular-expense')"
                  v-model="form.category_type"
                  type="radio"
                  value="regular_expense"
                >
              </div>

              <div class="m-4">
                <InputLabel
                  :for="getUuid('recurring-expense')"
                  value="Recurring Expense?"
                />
                <input
                  :id="getUuid('recurring-expense')"
                  v-model="form.category_type"
                  type="radio"
                  value="recurring_expense"
                >
              </div>

              <div class="m-4">
                <InputLabel
                  :for="getUuid('extra-expense')"
                  value="Extra Expense?"
                />
                <input
                  :id="getUuid('extra-expense')"
                  v-model="form.category_type"
                  type="radio"
                  value="extra_expense"
                >
              </div>

              <div class="m-4">
                <InputLabel
                  :for="getUuid('housing-expense')"
                  value="Housing?"
                />
                <input
                  :id="getUuid('housing-expense')"
                  v-model="form.category_type"
                  type="radio"
                  value="housing_expense"
                >
              </div>

              <div class="m-4">
                <InputLabel
                  :for="getUuid('utility-expense')"
                  value="Utility?"
                />
                <input
                  :id="getUuid('utility-expense')"
                  v-model="form.category_type"
                  type="radio"
                  value="utility_expense"
                >
              </div>
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
              @click="cancel"
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
