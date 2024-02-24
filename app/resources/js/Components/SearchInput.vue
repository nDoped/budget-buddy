<script setup>
  import { MagnifyingGlassIcon } from "@heroicons/vue/24/solid";

  defineProps({
    inputId: {
      type: String,
      default: "",
    },
    inputName: {
      type: String,
      required: true,
    },
    modelValue: {
      type: String,
      default: "",
    },
    labelName: {
      type: String,
      default: "",
    },
    regexPattern: {
      type: String,
      default: "",
    },
    screenReader: {
      type: Boolean,
      default() {
        return false;
      },
    },
    placeholder: {
      type: String,
      default: "",
    },
    addRequiredToLabel: {
      type: Boolean,
      default: false,
    },
    validationMode: {
      type: String,
      default: "passive",
    },
    help: {
      type: String,
      default: "",
    },
    hint: {
      type: String,
      default: "",
    },
  });

  const emit = defineEmits(["update:modelValue"]);
  const debounce = (fn, wait) => {
    let timer;
    return function(...args){
      if(timer) {
        clearTimeout(timer); // clear any pre-existing timer
      }
      const context = this; // get the current context
      timer = setTimeout(()=>{
        fn.apply(context, args); // call the function if time expires
      }, wait);
    }
  };
  const emitUpdate = (value) => {
    emit("update:modelValue", value);
  }
  const debouncedUpdateEmit = debounce(emitUpdate, 300);
</script>

<template>
  <div>
    <label>
      <p
        v-if="help"
        :id="inputName + '-help'"
        class="ls5-form-help"
        :class="{ 'sr-only': screenReader }"
      >
        {{ help }}
      </p>
      <div class="relative mt-1">
        <div
          class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3"
        >
          <MagnifyingGlassIcon
            class="h-5 w-5 text-surface-400 dark:text-surface-500"
            aria-hidden="true"
          />
        </div>
        <input
          :id="inputId"
          type="search"
          class="pl-10 pr-3 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
          :placeholder="placeholder"
          @keyup="debouncedUpdateEmit($event.target.value)"
        >
      </div>
      <p
        v-if="hint"
        :id="inputName + '-hint'"
        class="ls5-form-hint mt-1"
        :class="{ 'sr-only': screenReader }"
      >
        {{ hint }}
      </p>
    </label>
  </div>
</template>
