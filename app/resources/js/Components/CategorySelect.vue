<script setup>
  import Multiselect from 'vue-multiselect';
  import { computed } from 'vue';
  //import InputLabel from '@/Components/InputLabel.vue';
  import 'vue3-toastify/dist/index.css';

  defineEmits([ 'update:modelValue' ]);

  const model = defineModel();
  const props = defineProps({
    selectId: {
      type: String,
      default: ''
    },
    categoryTypes: {
      type: Array,
      default: () => []
    },
    availableCategories: {
      type: Array,
      default: () => []
    },
  });
  // @todo: This doesn't work but i'm leaving it for now
  // untill i can fix it
  //const preventBackspaceNavigation = (ev) => {
  //  if (ev.key === 'Backspace') {
  //    console.log({
  //      'resources/js/Components/TransactionCategory.vue:27 ev.key' : ev.key,
  //    });
  //    return ev.preventDefault();
  //  }
  //};

  const catSelectBorder = computed(() => {
    return `border: solid ${model.value.hex_color}; border-radius: 5px;`;
  });
  const fetchFilteredCatsOptions = computed(() => {
    let cats =  [ ...props.availableCategories ];
    cats.sort(function(a, b) {
      if (a.cat_type_name !== b.cat_type_name) {
        if (a.cat_type_name < b.cat_type_name) return -1;
        if (a.cat_type_name > b.cat_type_name) return 1;
      } else {
        if (a.name < b.name) return -1;
        if (a.name > b.name) return 1;
      }
      return 0;
    });
    let ret = [
      {
        'category_type': "No Category Type",
        'categories': []
      }
    ];
    cats.forEach((c) => {
      if (! c.cat_type_id) {
        let noCatType = ret.find((r) => r.category_type === "No Category Type");
        noCatType.categories.push(c);
        return;
      }
      let existing = ret.find((r) => r.category_type === c.cat_type_name);
      if (existing) {
        existing.categories.push(c);
      } else {
        ret.push({
          'category_type': c.cat_type_name,
          'categories': [c]
        });
      }
    });
    return ret;
  });
</script>

<template>
  <div :style="catSelectBorder">
    <!--
    <InputLabel
      value="Category"
    />
    -->

    <Multiselect
      :id="selectId"
      class="my-multiselect"
      v-model="model"
      track-by="cat_id"
      label="name"
      placeholder="Select a Category"
      deselect-label=""
      group-label="category_type"
      group-values="categories"
      :group-select="false"
      select-label=""
      :options="fetchFilteredCatsOptions"
      :allow-empty="false"
      :searchable="true"
    />
  </div>
  <!--
    @keyup.prevent.stop="preventBackspaceNavigation"
    -->
</template>

<style lang="css" src="vue-multiselect/dist/vue-multiselect.css"></style>

<style>

.my-multiselect .multiselect__tags {
  @apply bg-gray-400;
  min-height: 32px;
  display: block;
  padding: 3px 40px 0 8px;
  border-radius: 5px;
  border: 1px solid #e8e8e8;
  background: #fff;
  font-size: 14px;
}
.my-multiselect .multiselect {
  @apply bg-gray-400;
}
.my-multiselect .multiselect__option--highlight .multiselect__option {
  @apply bg-gray-400;
}
.my-multiselect .multiselect__single {
  @apply bg-gray-400;
}
.my-multiselect .multiselect__input {
  @apply bg-gray-400;
}
</style>
