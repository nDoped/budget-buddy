<script setup>
  import {
    ref,
    watch
  } from 'vue';
  import { useForm } from '@inertiajs/vue3'
  import { randomUUID } from '@/lib.js';
  import InputLabel from '@/Components/InputLabel.vue';
  import InputError from '@/Components/InputError.vue';
  import ConfirmationModal from '@/Components/ConfirmationModal.vue';
  import PrimaryButton from '@/Components/PrimaryButton.vue';
  import SecondaryButton from '@/Components/SecondaryButton.vue';
  import DangerButton from '@/Components/DangerButton.vue';
  import TextInput from '@/Components/TextInput.vue';
  import { toast } from 'vue3-toastify';
  import 'vue3-toastify/dist/index.css';

  const emit = defineEmits(['success', 'cancel']);

  const props = defineProps({
    categorySubtype: {
      type: Object,
      default: () => {}
    }
  });

  const form = useForm({
    name: props.categorySubtype.name,
  });

  watch(
    () => props.categorySubtype,
    () => {
      form.name = props.categorySubtype.name;
      deleteSubtypeForm.id = props.categorySubtype.id;
    }
  );

  const cancel = () => {
    emit('cancel');
  };

  const subtypeBeingDeleted = ref(null);

  const confirmDeletion = () => {
    subtypeBeingDeleted.value = props.categorySubtype.id;
  };

  const success = (deleting) => {
    subtypeBeingDeleted.value = null;
    toast.success((deleting) ? 'Category Subtype Deleted!' : 'Category Subtype Updated!');
    emit('success');
  };

  const deleteSubtypeForm = useForm({
    id: props.categorySubtype.id
  });

  const deleteSubtype = () => {
    deleteSubtypeForm.delete(route('category_subtypes.destroy', { id: subtypeBeingDeleted.value }), {
      preserveScroll: true,
      onSuccess: () => success(true),
      onError: (err) => {
        console.error(err.message)
        subtypeBeingDeleted.value = null;
        toast.error(err.message, {
          autoClose: 6000,
        });
      }
    });
  };

  function submit() {
    form.patch(route('category_subtypes.update', { categorySubtype: props.categorySubtype.id }), {
      preserveScroll: true,
      onSuccess: () => success(false),
      onError: (err) => {
        console.error(err.message)
        subtypeBeingDeleted.value = null;
        for (let field in err) {
          toast.error(err[field], {
            autoClose: 3000,
          });
        }
      }
    });
  }

  const uuid = randomUUID();
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
          :key="categorySubtype.id"
        >
          <div class="flex flex-col p-6 bg-slate-500 border-b border-gray-200">
            <div class="flex flex-row">
              <div class="m-2">
                <InputLabel
                  :for="getUuid('subtype-name')"
                  value="Name"
                />
                <TextInput
                  :id="getUuid('subtype-name')"
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
            </div>
          </div>

          <div class="flex flex-wrap p-3 bg-slate-500 border-gray-200">
            <PrimaryButton
              class="ml-3"
              type="submit"
              :class="{ 'opacity-25': deleteSubtypeForm.processing || form.processing }"
              :disabled="deleteSubtypeForm.processing || form.processing"
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
              :class="{ 'opacity-25': deleteSubtypeForm.processing || form.processing }"
              :disabled="deleteSubtypeForm.processing || form.processing"
              @click="confirmDeletion"
            >
              Delete
            </DangerButton>
            <ConfirmationModal
              :show="subtypeBeingDeleted != null"
              @close="subtypeBeingDeleted = null"
            >
              <template #title>
                Delete Category Subtype
              </template>

              <template #content>
                You sure you wanna delete this mofo?
              </template>

              <template #footer>
                <SecondaryButton @click="subtypeBeingDeleted = null">
                  Cancel
                </SecondaryButton>

                <DangerButton
                  class="ml-3"
                  :class="{ 'opacity-25': deleteSubtypeForm.processing }"
                  :disabled="deleteSubtypeForm.processing"
                  @click="deleteSubtype"
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
