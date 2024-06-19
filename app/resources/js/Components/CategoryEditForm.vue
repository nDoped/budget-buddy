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
  import CategoryInputs from '@/Components/CategoryInputs.vue';
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
    category_type: props.category.category_type_id
  });

  watch(
    () => props.category,
    () => {
      form.name = props.category.name;
      form.color = props.category.color;
      form.active = props.category.active;
      form.category_type = props.category.category_type_id;
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

  const updateInputs = ({ name, color, type, active }) => {
    form.name = name;
    form.color = color;
    form.category_type = type;
    form.active = active;
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
</script>

<template>
  <div class="py-2">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-slate-500 overflow-hidden shadow-sm sm:rounded-lg">
        <form
          @submit.prevent="submit"
          :key="category.id"
        >
          <CategoryInputs
            :errors="form.errors"
            :name="form.name"
            :type="form.category_type"
            :color="form.color"
            :active="form.active"
            :category-types="categoryTypes"
            :include-active-input="true"
            @field-update="updateInputs"
          />

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
