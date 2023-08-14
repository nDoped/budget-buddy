<script setup>
  import {
    ref,
    watch
  } from 'vue';
  import { useForm } from '@inertiajs/vue3'
  import InputLabel from '@/Components/InputLabel.vue';
  import InputError from '@/Components/InputError.vue';
  import ConfirmationModal from '@/Components/ConfirmationModal.vue';
  import Checkbox from '@/Components/Checkbox.vue';
  import PrimaryButton from '@/Components/PrimaryButton.vue';
  import SecondaryButton from '@/Components/SecondaryButton.vue';
  import DangerButton from '@/Components/DangerButton.vue';
  import TextInput from '@/Components/TextInput.vue';
  import { toast } from 'vue3-toastify';
  import 'vue3-toastify/dist/index.css';

  const emit = defineEmits(['success', 'cancel']);

  const props = defineProps({
    category: {
      type: Object,
      default: () => {}
    },
    categoryTypes: {
      type: Object,
      default: () => {}
    }
  });

  const form = useForm({
    name: props.category.name,
    color: props.category.color,
    active: props.category.active,
    category_type: (props.category.category_type_id) ?? ''
  });

  watch(
    () => props.category,
    () => {
      form.name = props.category.name;
      form.color = props.category.color;
      form.active = props.category.active;
      form.category_type = (props.category.category_type_id) ?? '';
      deleteCategoryForm.id = props.category.id;
    }
  );

  const cancel = () => {
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
  const getUuid = (el) => {
    return `${el}-${uuid}`;
  };
</script>

<template>
  <div class="py-2">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-slate-500 overflow-hidden shadow-sm sm:rounded-lg">
        <form
          @submit.prevent="submit"
          :key="category.id"
        >
          <div class="flex flex-col p-6 bg-slate-500 border-b border-gray-200">
            <div class="flex flex-row">
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
                  for="type"
                  value="Category Type"
                />
                <select
                  id="type"
                  v-model="form.category_type"
                  class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                >
                  <option
                    selected
                    value=""
                  >
                    Select type...
                  </option>

                  <option
                    v-for="(type, i) in categoryTypes"
                    :key="i"
                    :value="type.id"
                  >
                    {{ type.name }}
                  </option>
                </select>
              </div>

              <div class="m-4">
                <InputLabel
                  :for="getUuid('cat-color')"
                  value="Color"
                />
                <input
                  :id="getUuid('cat-color')"
                  type="color"
                  v-model="form.color"
                >
              </div>

              <div class="m-4">
                <InputLabel
                  :for="getUuid('cat-active')"
                  value="Active"
                />
                <Checkbox
                  v-model:checked="form.active"
                  name="active"
                />
              </div>
            </div>
          </div>

          <div class="flex flex-wrap p-3 bg-slate-500 border-gray-200">
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
