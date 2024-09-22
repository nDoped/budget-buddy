<script setup lang=ts>
  import { ref, onMounted } from "vue";
  import PrimaryButton from '@/Components/PrimaryButton.vue';
  import SecondaryButton from '@/Components/SecondaryButton.vue';
  import InputLabel from '@/Components/InputLabel.vue';
  import TextInput from '@/Components/TextInput.vue';
  import { focusElement } from '@/lib.js';
  const emit = defineEmits(['cancel', 'update:modelValue']);
  const cancel = () => {
    emit('cancel');
  };

  // Camera
  const canvas = ref(null);
  const video = ref(null);
  const ctx = ref(null);
  const constraints = ref({
    video: {
      width: {
        min: 500,
      },
      height: {
        min: 400,
      },
      facingMode: 'environment',
    },
    audio: false,
  });
  onMounted(async () => {
    if ('mediaDevices' in navigator && 'getUserMedia' in navigator.mediaDevices) {
      await navigator.mediaDevices
        .getUserMedia(constraints.value)
        .then(SetStream)
        .catch((err) => {
          console.error("Error accessing the camera", err);
        });
      if (video.value && canvas.value) {
        video.value.onloadedmetadata = () => {
          if (canvas.value) {
            canvas.value.width = video.value.videoWidth;
            canvas.value.height = video.value.videoHeight;
          }
        };
        ctx.value = canvas.value.getContext("2d");
      }
    }
  });
  function SetStream(stream) {
    video.value.srcObject = stream;
    video.value.play();
    requestAnimationFrame(Draw);
  }
  function Draw() {
    if (video.value && canvas.value) {
      ctx.value.drawImage(video.value, 0, 0, canvas.value.width, canvas.value.height);
      requestAnimationFrame(Draw);
    }
  }

  // model
  const model = defineModel({
    type: Object,
    default: () => {
      return {
        base64: {
          type: String,
          default: "",
        },
        name: {
          type: String,
          default: "",
        }
      };
    }
  });
  const base64= ref(null);
  const name = ref(null);
  onMounted(() => focusElement(getUuid('take-pic-btn')));
  const takePic = () => {
    let data = canvas.value.toDataURL('image/png', 1);
    base64.value = data;
    focusElement(getUuid('file-name'));
  };

  const retakePic = () => {
    base64.value = null;
    focusElement(getUuid('take-pic-btn'));
  };
  const savePic = () => {
    model.value.base64 = base64
    model.value.name = name
    emit('update:modelValue', model.value);
  };
  const uuid = crypto.randomUUID();
  const getUuid = (el, i = 0) => {
    return `${el}-${i}-${uuid}`;
  };
</script>

<template>
  <div class="min-h-screen flex flex-col items-center justify-center">
    <video
      ref="video"
      autoplay
      playsinline
      webkit-playsinline
      muted
      hidden
    />

    <canvas
      v-show="! base64"
      ref="canvas"
      width="video.videoWidth"
      height="video.videoHeight"
      style="object-fit: contain"
      class="bg-black rounded-3xl"
    />
    <img
      v-if="base64"
      :src="base64"
    >

    <div class="flex items-center justify-center py-4">
      <PrimaryButton
        v-if="! base64"
        :id="getUuid('take-pic-btn')"
        type="button"
        @click="takePic"
      >
        Take Pic
      </PrimaryButton>

      <template v-else>
        <PrimaryButton
          :id="getUuid('save-pic-btn')"
          type="button"
          @click="savePic"
        >
          Save Pic
        </PrimaryButton>

        <SecondaryButton
          type="button"
          @click="retakePic"
        >
          Retake Pic
        </SecondaryButton>
      </template>
      <SecondaryButton
        type="button"
        @click="cancel"
      >
        Cancel
      </SecondaryButton>
    </div>
    <InputLabel
      :for="getUuid('file-name')"
      value="File Name"
    />
    <TextInput
      :id="getUuid('file-name')"
      ref="inputRef"
      v-model="name"
      label="File Name"
      type="text"
      @keydown.enter="savePic"
      class="mt-2"
    />
  </div>
</template>
