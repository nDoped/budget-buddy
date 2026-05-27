<script setup>
  import {
    ref,
    computed,
  } from 'vue';
  import { randomUUID } from '@/lib.js';
  import Camera from '@/Components/CameraComponent.vue';
  import PrimaryButton from '@/Components/PrimaryButton.vue';
  import SecondaryButton from '@/Components/SecondaryButton.vue';
  import ConfirmationModal from '@/Components/ConfirmationModal.vue';
  import CameraModal from '@/Components/CameraModal.vue';
  import ActionMessage from '@/Components/ActionMessage.vue';
  import TransactionImage from '@/Components/TransactionImage.vue';
  import { toast } from 'vue3-toastify';
  import 'vue3-toastify/dist/index.css';
  import axios from 'axios';

  /*
   * Models
   */
  const newImages = defineModel('newImages', {
    type: Array,
    default: []
  });
  const uploadedFile = defineModel('uploadedFile', {
    type: Object,
    default: {}
  });
  const existingImages = defineModel('existingImages', {
    type: Array,
    default: []
  });
  const deletedImageIds = defineModel('deletedImageIds', {
    type: Array,
    default: []
  });

  /*
   * Camera
   */
  const showCameraModal = ref(false);
  const cancelImageCapture = () => {
    showCameraModal.value = false;
  };

  const addAnImage = () => {
    showCameraModal.value = true;
  };
  const saveImage = (val) => {
    showCameraModal.value = false;
    newImages.value.push({
      base64: val.base64,
      name: val.name
    });
  };
  const deleteExistingImage = (image) => {
    existingImages.value = existingImages.value.filter(i => i.id !== image.id);
    deletedImageIds.value = [...deletedImageIds.value, image.id];
  };

  /*
   * File Upload
   */
  const uploadFileInput = ref(null);
  const showFileUploadModal = ref(false);
  const photoPreview = ref(null);
  const cancelFileUpload = () => {
    showFileUploadModal.value = false;
    photoPreview.value = null;
  };
  const selectNewPhoto = () => {
    uploadFileInput.value.click();
  };
  const updatePhotoPreview = () => {
    const photo = uploadFileInput.value.files[0];

    if (! photo) return;

    const reader = new FileReader();

    reader.onload = (e) => {
      photoPreview.value = e.target.result;
    };

    if (photo.type === 'application/pdf') {
      photoPreview.value = 'pdf:' + photo.name;
    } else {
      reader.readAsDataURL(photo);
    }
  };

  const isPdfPreview = computed(() => {
    return typeof photoPreview.value === 'string' && photoPreview.value.startsWith('pdf:');
  });
  const saveFileUpload = () => {
    let file = uploadFileInput.value.files[0];
    uploadedFile.value = file;
    showFileUploadModal.value = false;
    photoPreview.value = null;
  };

  /*
   * AI Receipt Analysis
   */
  const emit = defineEmits(['analyze-receipt']);
  const analyzing = ref(false);
  const forceRefresh = ref(false);
  const analyzeImage = async () => {
    if (newImages.value.length === 0 && ! uploadedFile.value) {
      toast.error('Please capture or upload an image/PDF first', { autoClose: 3000 });
      return;
    }

    let imageData = null;
    if (newImages.value.length > 0) {
      imageData = newImages.value[0].base64;
    } else if (uploadedFile.value) {
      imageData = await fileToBase64(uploadedFile.value);
    }

    if (! imageData) {
      toast.error('No image data found', { autoClose: 3000 });
      return;
    }

    analyzing.value = true;
    try {
      /* global axios route */
      const response = await axios.post(route('receipt.analyze'), {
        image: imageData,
        force_refresh: forceRefresh.value,
      });

      const result = response.data;

      toast.success('Receipt analyzed successfully!', { autoClose: 3000 });

      if (newImages.value.length > 0) {
        newImages.value[0].ai_analysis = {
          store_name: result.store_name,
          line_items: result.line_items,
          subtotal: result.subtotal,
          tax: result.tax,
          total: result.total,
          date: result.date,
          suggested_account_id: result.suggested_account_id,
        };
      }

      emit('analyze-receipt', {
        store_name: result.store_name,
        line_items: result.line_items,
        subtotal: result.subtotal,
        tax: result.tax,
        total: result.total,
        date: result.date,
        suggested_account_id: result.suggested_account_id,
      });
    } catch (err) {
      const msg = err.response?.data?.error || err.message || 'Failed to analyze receipt';
      toast.error(msg, { autoClose: 6000 });
    } finally {
      analyzing.value = false;
    }
  };

  const fileToBase64 = (file) => {
    return new Promise((resolve, reject) => {
      const reader = new FileReader();
      reader.onload = () => resolve(reader.result);
      reader.onerror = () => reject(new Error('Failed to read file'));
      reader.readAsDataURL(file);
    });
  };

  const uuid = randomUUID();
  const getUuid = (el, i = 0) => {
    return `${el}-${i}-${uuid}`;
  };
</script>

<template>
  <div class="py-2">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-slate-500 sm:rounded-lg">
        <div class="flex flex-row items-start">
          <SecondaryButton
            class="grow-0"
            type="button"
            @click="addAnImage"
          >
            Capture an image
          </SecondaryButton>

          <div>
            <div class="flex flex-col">
              <SecondaryButton
                type="button"
                @click="showFileUploadModal = true"
              >
                Upload image/PDF
              </SecondaryButton>

              <div
                v-if="uploadedFile !== null"
                class="flex flex-row items-center ml-2"
              >
                <ActionMessage
                  :on="uploadedFile !== null"
                >
                  {{ uploadedFile.name }}
                </ActionMessage>
                <button
                  type="button"
                  class="ml-2 text-red-500 hover:text-red-700 text-sm font-medium"
                  @click="uploadedFile = null"
                >
                  Remove
                </button>
              </div>
            </div>
          </div>
        </div>

        <div class="m-2 mb-4 min-w-min flex-none relative ml-auto">
          <div class="m-4 flex flex-row place-content-between">
            <div v-if="existingImages && existingImages.length > 0">
              <h1 class="text-xl font-bold text-gray-700 dark:text-gray-300 text-center">Existing</h1>
              <div class="flex flex-row flex-wrap">
                <TransactionImage
                  v-for="(image, index) in existingImages"
                  :key="index"
                  v-model="existingImages[index]"
                  @delete="deleteExistingImage(image)"
                />
              </div>
            </div>

            <div v-if="newImages && newImages.length > 0">
              <h1 class="text-xl font-bold text-gray-700 dark:text-gray-300 text-center">New</h1>
              <div class="flex flex-row flex-wrap">
                <TransactionImage
                  v-for="(image, index) in newImages"
                  :key="index"
                  v-model="newImages[index]"
                  @delete="newImages.splice(index, 1)"
                />
              </div>
            </div>
          </div>

          <div
            v-if="newImages.length > 0 || uploadedFile"
            class="mt-4 flex flex-row items-center gap-3"
          >
            <PrimaryButton
              type="button"
              :class="{ 'opacity-25': analyzing }"
              :disabled="analyzing"
              @click="analyzeImage"
            >
              {{ analyzing ? 'Analyzing...' : 'Analyze with AI' }}
            </PrimaryButton>
            <label class="flex items-center gap-1 text-sm cursor-pointer select-none">
              <input
                type="checkbox"
                v-model="forceRefresh"
                class="rounded"
              />
              Refresh
            </label>
          </div>
        </div>
      </div>
    </div>

    <CameraModal
      :show="showCameraModal"
      max-width="5xl"
      @close="showCameraModal = false"
    >
      <template #title>
        Image Capture
      </template>

      <template #content>
        <Camera
          @cancel="cancelImageCapture"
          @update:model-value="saveImage($event)"
        />
      </template>
    </CameraModal>

    <!-- File Upload -->
    <ConfirmationModal
      :show="showFileUploadModal"
      @close="showFileUploadModal = false"
    >
      <template #title>
        Upload a file
      </template>

      <template #content>
        <div
          v-show="photoPreview"
          class="mt-2"
        >
          <span
            v-if="isPdfPreview"
            class="block text-gray-700 dark:text-gray-300 text-sm"
          >
            {{ uploadedFile?.name || 'PDF selected' }}
          </span>
          <span
            v-else
            class="block w-20 h-20 bg-cover bg-no-repeat bg-center"
            :style="'background-image: url(\'' + photoPreview + '\');'"
          />
        </div>
      </template>

      <template #footer>
        <input
          ref="uploadFileInput"
          type="file"
          accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,image/jpeg,image/png,image/gif,image/webp,application/pdf"
          class="hidden"
          @change="updatePhotoPreview"
        >
        <PrimaryButton
          class="mt-2 mr-2"
          :id="getUuid('receipt-upload')"
          type="button"
          @click.prevent="selectNewPhoto"
        >
          Select a file
        </PrimaryButton>

        <SecondaryButton
          class="mt-2 mr-2"
          @click="cancelFileUpload"
        >
          Cancel
        </SecondaryButton>

        <PrimaryButton
          class="mt-2 mr-2"
          type="button"
          @click="saveFileUpload()"
        >
          Save
        </PrimaryButton>
      </template>
    </ConfirmationModal>
  </div>
</template>
