<script setup>
  import {
    ref,
  } from 'vue';
  import { useForm } from '@inertiajs/vue3'
  import InputLabel from '@/Components/InputLabel.vue';
  import InputDate from '@/Components/InputDate.vue';
  import InputError from '@/Components/InputError.vue';
  import Camera from '@/Components/CameraComponent.vue';
  //import SectionBorder from '@/Components/SectionBorder.vue';
  import PrimaryButton from '@/Components/PrimaryButton.vue';
  import SecondaryButton from '@/Components/SecondaryButton.vue';
  import DangerButton from '@/Components/DangerButton.vue';
  import ConfirmationModal from '@/Components/ConfirmationModal.vue';
  import CameraModal from '@/Components/CameraModal.vue';
  import ActionMessage from '@/Components/ActionMessage.vue';
  import TextArea from '@/Components/TextArea.vue';
  import { forceNumericalInput } from '@/lib.js';

  defineProps({
    errors: {
      type: Object,
      default: () => {}
    },
    accounts: {
      type: Array,
      default: () => {}
    }
  });
  const model = defineModel({
    type: Object,
    default: () => {
      return {
        transaction_date: '',
        amount: '',
        credit: false,
        account_id: '',
        note: '',
        bank_identifier: '',
        images_base64: [],
        images: []
      };
    }
  });
  const showCameraModal = ref(false);
  const cancelReceiptImageCapture = () => {
    showCameraModal.value = false;
  };

  const addAnImage = () => {
    showCameraModal.value = true;
  };
  const saveReceiptImage = (val) => {
    showCameraModal.value = false;
    model.value.images_base64.push(val.value);
  };
  const deleteExistingImage = (image) => {
    model.value.images = model.value.images.filter(i => i.id !== image.id);
  };

  //const photoInput = ref(null);
  //const showReceiptModal = ref(false);
  //const photoPreview = ref(null);
  //const cancelReceiptUpload = () => {
  //  showReceiptModal.value = false;
  //  photoPreview.value = null;
  //};
  //const selectNewPhoto = () => {
  //  photoInput.value.click();
  //};
  //const receiptUploadForm = useForm({
  //  photo: null,
  //});
  //const updatePhotoPreview = () => {
  //  const photo = photoInput.value.files[0];

  //  if (! photo) return;

  //  const reader = new FileReader();

  //  reader.onload = (e) => {
  //    photoPreview.value = e.target.result;
  //  };

  //  reader.readAsDataURL(photo);
  //};
  //const clearPhotoFileInput = () => {
  //  if (photoInput.value?.value) {
  //    photoInput.value.value = null;
  //  }
  //};
  //const uploadReceipt = () => {
  //  /*
  //  if (photoInput.value) {
  //    receiptUploadForm.photo = photoInput.value.files[0];
  //  }
  //   */
  //  receiptUploadForm.processing = true;

  //  let formData = new FormData();
  //  formData.append("photo", photoInput.value.files[0]);

  //  /* global axios */
  //  axios.post(
  //    route('transactions.upload_receipt'),
  //    formData,
  //    {
  //      headers: {
  //        'Content-Type': 'multipart/form-data'
  //      }
  //    }
  //  ).then((response) => {
  //    console.log({
  //      'resources/js/Components/TransactionCategory.vue:153 k' : response,
  //    });
  //    receiptUploadForm.processing = false;
  //    //showReceiptModal.value = false;

  //    clearPhotoFileInput();
  //    //nextTick().then(() => emit('confirmed'));

  //  }).catch(error => {
  //    receiptUploadForm.processing = false;
  //    console.log({
  //      'resources/js/Components/TransactionCategory.vue:163 error' : error,
  //    });
  //    //receiptUploadForm.error = error.response.data.errors.password[0];

  //  });

  //  /* global route */
  //  /*
  //  receiptUploadForm.post(route('transactions.upload_receipt'), {
  //    preserveScroll: true,
  //    preserveState: true,
  //    onSuccess: () => clearPhotoFileInput(),
  //  });
  //   */
  //};
  const uuid = crypto.randomUUID();
  const getUuid = (el, i = 0) => {
    return `${el}-${i}-${uuid}`;
  };
</script>

<template>
  <div class="py-2 border-b border-gray-200">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-slate-500 sm:rounded-lg">
        <div class="flex flex-wrap p-1 ">
          <div class="m-2">
            <!-- date -->
            <div class="m-2">
              <InputLabel
                for="date"
                value="Transaction Date"
              />
              <InputDate
                id="getUuid('transaction-date')"
                v-model="model.transaction_date"
              />
              <InputError
                :message="errors.transaction_date"
                class="mt-2"
              />
            </div>

            <!-- amount -->
            <div class="m-2">
              <InputLabel
                for="amount"
                value="Amount"
              />
              <input
                type="number"
                min="0"
                step="any"
                v-model="model.amount"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                @keypress="forceNumericalInput($event)"
              >
              <InputError
                :message="errors.amount"
                class="mt-2"
              />
            </div>
          </div>


          <div class="m-2">
            <!-- credit/debit -->
            <div class="m-2">
              <InputLabel
                for="credit"
                value="Credit/Debit"
              />
              <select
                id="getUuid('credit-select')"
                v-model="model.credit"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
              >
                <option :value="true">
                  Credit
                </option>
                <option
                  selected="selected"
                  :value="false"
                >
                  Debit
                </option>
              </select>
              <InputError
                :message="errors.credit"
                class="mt-2"
              />
            </div>

            <!-- accounts -->
            <div class="m-2">
              <InputLabel
                for="account"
                value="Account"
              />
              <select
                id="getUuid('account-select')"
                v-model="model.account_id"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
              >
                <option
                  value=""
                  selected
                  disabled
                  hidden
                >
                  Select Account...
                </option>

                <option
                  v-for="account in accounts"
                  :key="account.id"
                  :value="account.id"
                >
                  {{ account.name }}
                </option>
              </select>

              <InputError
                :message="errors.account_id"
                class="mt-2"
              />
            </div>
          </div>

          <div class="m-2 grow">
            <div class="m-1">
              <InputLabel
                for="note"
                value="Note"
              />
              <TextArea
                id="getUuid('note')"
                v-model="model.note"
                class="mt-1 block w-full"
                autofocus
                autocomplete="note"
              />
              <InputError
                :message="errors.note"
                class="mt-2"
              />
            </div>

            <div class="m-1">
              <InputLabel
                for="bank_ident"
                value="Bank Identifier"
              />
              <TextArea
                id="getUuid('bank_ident')"
                v-model="model.bank_identifier"
                type="text"
                class="mt-1 block w-full"
                autofocus
                autocomplete="bank_ident"
              />
            </div>
          </div>
        </div>

        <div class="m-2 mb-4 min-w-min flex-none order-last relative ml-auto">
          <!--
          <SecondaryButton
            type="button"
            @click="showReceiptModal = true"
          >
            Upload a receipt
          </SecondaryButton>
          -->

          <SecondaryButton
            type="button"
            @click="addAnImage"
          >
            Add an image
          </SecondaryButton>

          <template v-if="model.images && model.images.length > 0">
            <p class="m-2">
              Existing Images
            </p>
            <div class="flex flex-row">
              <div
                v-for="(image, index) in model.images"
                :key="index"
                class=" w-20 h-20 bg-cover bg-no-repeat bg-center "
              >
                <div class="flex flex-col m-2">
                  <img
                    :src="image.path"
                    class="transition-transform duration-200 hover:scale-105"
                  >
                  <DangerButton
                    class="max-h-1"
                    type="button"
                    @click="deleteExistingImage(image)"
                  >
                    Delete
                  </DangerButton>
                </div>
              </div>
            </div>
          </template>

          <template v-if="model.images_base64 && model.images_base64.length > 0">
            <p class="m-2">
              New Images
            </p>
            <div class="flex flex-row">
              <div
                v-for="(image, index) in model.images_base64"
                :key="index"
                class="w-20 h-20 bg-cover bg-no-repeat bg-center "
              >
                <div class="flex flex-col m-2">
                  <img
                    :src="image"
                    class="transition-transform duration-200 hover:scale-105"
                  >
                  <DangerButton
                    class="max-h-1"
                    type="button"
                    @click="model.images_base64.splice(index, 1)"
                  >
                    Delete
                  </DangerButton>
                </div>
              </div>
            </div>
          </template>
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
      Capture Receipt Image
    </template>

    <template #content>
      <Camera
        @cancel="cancelReceiptImageCapture"
        @update:model-value="saveReceiptImage($event)"
      />
    </template>
  </CameraModal>

  <!--
  <ConfirmationModal
    :show="showReceiptModal"
    @close="showReceiptModal = false"
  >
    <template #title>
      Upload a receipt
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
        ref="photoInput"
        type="file"
        class="hidden"
        @change="updatePhotoPreview"
      >

      <PrimaryButton
        class="mt-2 mr-2"
        :id="getUuid('receipt-upload')"
        type="button"
        :class="{ 'opacity-25': receiptUploadForm.processing }"
        :disabled="receiptUploadForm.processing"
        @click.prevent="selectNewPhoto"
      >
        Select an image
      </PrimaryButton>

      <SecondaryButton
        class="mt-2 mr-2"
        :class="{ 'opacity-25': receiptUploadForm.processing }"
        @click="cancelReceiptUpload"
        :disabled="receiptUploadForm.processing"
      >
        Cancel
      </SecondaryButton>

      <ActionMessage
        :on="receiptUploadForm.recentlySuccessful"
        class="mr-3"
      >
        Saved.
      </ActionMessage>

      <PrimaryButton
        class="mt-2 mr-2"
        type="button"
        @click="uploadReceipt()"
        :class="{ 'opacity-25': receiptUploadForm.processing }"
        :disabled="receiptUploadForm.processing"
      >
        Upload
      </PrimaryButton>
    </template>
  </ConfirmationModal>
  -->
</template>
