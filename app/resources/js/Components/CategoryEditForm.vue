<script setup>
  import {
    ref,
    computed,
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
    },
    allCategories: {
      type: Array,
      default: () => []
    }
  });

  const form = useForm({
    name: props.category.name,
    hex_color: props.category.hex_color,
    active: props.category.active,
    category_type: props.category.category_type_id
  });

  watch(
    () => props.category,
    () => {
      form.name = props.category.name;
      form.hex_color = props.category.hex_color;
      form.active = props.category.active;
      form.category_type = props.category.category_type_id;
      deleteCategoryForm.id = props.category.id;
      mergeTargetId.value = null;
      catBeingMerged.value = false;
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

  const mergeTargetId = ref(null);
  const catBeingMerged = ref(false);
  const mergeForm = useForm({
    target_category_id: null
  });

  const mergeTargetOptions = computed(() => {
    return props.allCategories.filter(c => c.id !== props.category.id);
  });

  const confirmMerge = () => {
    catBeingMerged.value = true;
  };

  const mergeCategory = () => {
    mergeForm.target_category_id = mergeTargetId.value;
    mergeForm.post(route('categories.merge', { category: props.category.id }), {
      preserveScroll: true,
      onSuccess: () => {
        const targetName = mergeTargetOptions.value.find(c => c.id === mergeTargetId.value)?.name;
        catBeingMerged.value = false;
        mergeTargetId.value = null;
        toast.success(`"${props.category.name}" merged into "${targetName}"`);
        emit('success');
      },
      onError: (err) => {
        catBeingMerged.value = false;
        for (let field in err) {
          toast.error(err[field], {
            autoClose: 6000,
          });
        }
      }
    });
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

  const updateInputs = ({ name, hex_color, type, active }) => {
    form.name = name;
    form.hex_color = hex_color;
    form.category_type = type;
    form.active = active;
  };

  function submit() {
    /* global route */
    form.patch(route('categories.update', { category: props.category.id }), {
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
            :color="form.hex_color"
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

            <SecondaryButton
              class="ml-3"
              :class="{ 'opacity-25': deleteCategoryForm.processing || form.processing || mergeForm.processing }"
              :disabled="deleteCategoryForm.processing || form.processing || mergeForm.processing"
              @click="confirmMerge"
            >
              Merge
            </SecondaryButton>
            <DangerButton
              class="ml-3"
              :class="{ 'opacity-25': deleteCategoryForm.processing || form.processing || mergeForm.processing }"
              :disabled="deleteCategoryForm.processing || form.processing || mergeForm.processing"
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

            <ConfirmationModal
              :show="catBeingMerged"
              @close="catBeingMerged = false"
            >
              <template #title>
                Merge Category
              </template>

              <template #content>
                <p class="mb-3">
                  Merge <strong>{{ category.name }}</strong> into which category?
                </p>
                <select
                  v-model="mergeTargetId"
                  class="w-full px-4 py-2 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100"
                >
                  <option :value="null" disabled>
                    Select a category...
                  </option>
                  <option
                    v-for="cat in mergeTargetOptions"
                    :key="cat.id"
                    :value="cat.id"
                  >
                    {{ cat.name }}
                  </option>
                </select>
              </template>

              <template #footer>
                <SecondaryButton @click="catBeingMerged = false">
                  Cancel
                </SecondaryButton>

                <DangerButton
                  class="ml-3"
                  :class="{ 'opacity-25': mergeForm.processing || !mergeTargetId }"
                  :disabled="mergeForm.processing || !mergeTargetId"
                  @click="mergeCategory"
                >
                  Merge
                </DangerButton>
              </template>
            </ConfirmationModal>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
