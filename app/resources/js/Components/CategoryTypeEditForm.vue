<script setup>
  import {
    ref,
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
  import TextArea from '@/Components/TextArea.vue';
  import { toast } from 'vue3-toastify';
  import 'vue3-toastify/dist/index.css';

  const emit = defineEmits(['success', 'cancel']);

  const props = defineProps({
    categoryType: {
      type: Object,
      default: () => {}
    }
  });

  const form = useForm({
    name: props.categoryType.name,
    note: props.categoryType.note,
    color: props.categoryType.color,
  });

  watch(
    () => props.categoryType,
    () => {
      form.name = props.categoryType.name;
      form.note = props.categoryType.note;
      form.color = props.categoryType.color;
      deleteCategoryTypeForm.id = props.categoryType.id;
    }
  );

  const cancel = () => {
    emit('cancel');
  };

  const catTypeBeingDeleted = ref(null);

  const confirmCatDeletion = () => {
    catTypeBeingDeleted.value = props.categoryType.id;
  };

  const success = (deleting) => {
    catTypeBeingDeleted.value = null;
    toast.success((deleting) ? 'Category Type Deleted!' : 'Category Type Updated!');
    emit('success');
  };

  const deleteCategoryTypeForm = useForm({
    id:props.categoryType.id
  });

  const deleteCategory = () => {
    deleteCategoryTypeForm.delete(route('category_types.destroy', {id: catTypeBeingDeleted.value}), {
      preserveScroll: true,
      onSuccess: () => success(true),
      onError: (err) =>  {
        console.error(err.message)
        catTypeBeingDeleted.value = null;
        toast.error(err.message, {
          autoClose: 6000,
        });
      }
    });
  };

  function submit() {
    /* global route */
    form.post(route('category_types.update', { categoryType: props.categoryType.id }), {
      preserveScroll: true,
      onSuccess: () => success(false),
      onError: (err) =>  {
        console.error(err.message)
        catTypeBeingDeleted.value = null;
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
          :key="categoryType.id"
        >
          <div class="flex flex-col p-6 bg-slate-500 border-b border-gray-200">
            <div class="flex flex-row">
              <div class="m-2">
                <InputLabel
                  :for="getUuid('catt-name')"
                  value="Name"
                />
                <TextInput
                  :id="getUuid('catt-name')"
                  v-model="form.name"
                  type="text"
                  class="mt-1 block w-full"
                  autofocus
                  autocomplete="name"
                />
                <InputError
                  :message="form.errors.name"
                  class="mt-2"
                />
              </div>

              <div class="m-4">
                <InputLabel
                  :for="getUuid('catt-color')"
                  value="color"
                />
                <input
                  :id="getUuid('catt-color')"
                  type="color"
                  v-model="form.color"
                >
              </div>

              <div class="m-2">
                <InputLabel
                  :for="getUuid('catt-note')"
                  value="Note"
                />
                <TextArea
                  :id="getUuid('catt-note')"
                  v-model="form.note"
                  type="text"
                  class="mt-1 block w-full"
                  autocomplete="note"
                />
              </div>
            </div>
          </div>

          <div class="flex flex-wrap p-3 bg-slate-500 border-gray-200">
            <PrimaryButton
              class="ml-3"
              type="submit"
              :class="{ 'opacity-25': deleteCategoryTypeForm.processing || form.processing }"
              :disabled="deleteCategoryTypeForm.processing || form.processing"
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
              :class="{ 'opacity-25': deleteCategoryTypeForm.processing || form.processing }"
              :disabled="deleteCategoryTypeForm.processing || form.processing"
              @click="confirmCatDeletion"
            >
              Delete
            </DangerButton>
            <ConfirmationModal
              :show="catTypeBeingDeleted != null"
              @close="catTypeBeingDeleted = null"
            >
              <template #title>
                Delete Category
              </template>

              <template #content>
                You sure you wanna delete this mofo?
              </template>

              <template #footer>
                <SecondaryButton @click="catTypeBeingDeleted = null">
                  Cancel
                </SecondaryButton>

                <DangerButton
                  class="ml-3"
                  :class="{ 'opacity-25': deleteCategoryTypeForm.processing }"
                  :disabled="deleteCategoryTypeForm.processing"
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
