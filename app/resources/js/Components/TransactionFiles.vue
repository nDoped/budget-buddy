<script setup>
  import {
    ref
  } from 'vue';
  import Camera from '@/Components/CameraComponent.vue';
  import PrimaryButton from '@/Components/PrimaryButton.vue';
  import SecondaryButton from '@/Components/SecondaryButton.vue';
  import ConfirmationModal from '@/Components/ConfirmationModal.vue';
  import CameraModal from '@/Components/CameraModal.vue';
  import ActionMessage from '@/Components/ActionMessage.vue';
  import TransactionImage from '@/Components/TransactionImage.vue';

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
      base64: val.base64.value,
      name: val.name.value
    });
  };
  const deleteExistingImage = (image) => {
    existingImages.value = existingImages.value.filter(i => i.id !== image.id);
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

    reader.readAsDataURL(photo);
  };
  const saveFileUpload = () => {
    let file = uploadFileInput.value.files[0];
    uploadedFile.value = file;
    showFileUploadModal.value = false;
    photoPreview.value = null;
  };

  const uuid = crypto.randomUUID();
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
                Upload a file
              </SecondaryButton>

              <ActionMessage
                v-if="uploadedFile !== null"
                :on="uploadedFile !== null"
                class="ml-2"
              >
                {{ uploadedFile.name }}
              </ActionMessage>
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
            class="block w-20 h-20 bg-cover bg-no-repeat bg-center"
            :style="'background-image: url(\'' + photoPreview + '\');'"
          />
        </div>
      </template>

      <template #footer>
        <input
          ref="uploadFileInput"
          type="file"
          class="hidden"
          @change="updatePhotoPreview"
        >
        <PrimaryButton
          class="mt-2 mr-2"
          :id="getUuid('receipt-upload')"
          type="button"
          @click.prevent="selectNewPhoto"
        >
          Select an image
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
