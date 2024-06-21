<script setup>
  import Multiselect from 'vue-multiselect';
  import {
    toRaw,
    ref,
    watch,
    computed
  } from 'vue';
  import InputLabel from '@/Components/InputLabel.vue';
  import InputError from '@/Components/InputError.vue';
  import CategoryInputs from '@/Components/CategoryInputs.vue';
  import PrimaryButton from '@/Components/PrimaryButton.vue';
  import DangerButton from '@/Components/DangerButton.vue';
  import 'vue3-toastify/dist/index.css';

  const emit = defineEmits(['category-update', 'invalid-category-state']);

  const props = defineProps({
    categories: {
      type: Array,
      default: () => []
    },
    categoryTypes: {
      type: Array,
      default: () => []
    },
    availableCategories: {
      type: Array,
      default: () => []
    },
    errors: {
      type: Object,
      default: () => {return {}}
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

  const filteredCats = computed(() => {
    return props.availableCategories.filter((ac) => {
      return ! catsRef.value.find(cr => cr.cat_data.cat_id == ac.cat_id);
    });
  });
  const fetchFilteredCatsOptions = (cat) => {
    let cats = [ ...filteredCats.value, cat.cat_data ];
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
  };

  const catsRef = ref(structuredClone(toRaw(props.categories)));
  watch(
    () => props.categories,
    () => {
      catsRef.value = structuredClone(toRaw(props.categories))
    }
  );

  const percentError = ref(null);
  const percentTotal = computed(() => {
    let percentTotal = 0;
    catsRef.value.forEach((c) => {
      percentTotal += c.percent;
    });
    return Math.round((percentTotal + Number.EPSILON) * 100) / 100
  });
  const getError = (i) => {
    if (props.errors[`categories.${i}.name`]) {
      let ret = 'The name field is required';
      return {name: ret};
    }
  };
  watch(
    percentTotal,
    (p) => {
      if  (p === 100 || p === 0) {
        percentError.value = null
      } else if (p > 100) {
        percentError.value = "Percentages must sum to 100%. You are over by " + Math.round(((p - 100) + Number.EPSILON) * 100) / 100;
      } else {
        percentError.value = "Percentages must sum to 100%. You are under by " + Math.round(((100 - p) + Number.EPSILON) * 100) / 100;
      }
    }
  );

  const catSelectBorder = (cat) => {
    return `border: solid ${cat.cat_data.color}; border-radius: 5px;`;
  };
  const uuid = crypto.randomUUID();
  const getUuid = (el, i) => {
    return `${el}-${i}-${uuid}`;
  };
  const catChange = () => {
    if (percentTotal.value === 100 || catsRef.value.length === 0) {
      // transform catsRef back into the structure the parent component expects
      let cats = catsRef.value.map((c) => {
        return {
          ...c.cat_data,
          percent: c.percent
        };
      });
      emit('category-update', cats);
    } else {
      emit('invalid-category-state');
    }
  };

  const removeCategory = (i) => {
    catsRef.value.splice(i, 1);
  };
  const addCategory = () => {
    catsRef.value.push({
      "cat_data": {
        cat_id: filteredCats.value[0].cat_id,
        name: filteredCats.value[0].name,
        cat_type_id: filteredCats.value[0].cat_type_id,
        cat_type_name: filteredCats.value[0].cat_type_name,
        color: filteredCats.value[0].color,
      },
      percent: 0,
    });
  };
  const createCategory = (i, data) => {
    catsRef.value[i].cat_data.name = data.name;
    catsRef.value[i].cat_data.color = data.color;
    catsRef.value[i].cat_data.cat_type_id = data.type;
  };
  const createANewCategory = () => {
    catsRef.value.push({
      "cat_data" : {
        cat_id: null,
        name: null,
        cat_type_id: null,
        cat_type_name: null,
        color: '#000000',
      },
      percent: 0,
    });
  };
  watch(
    catsRef.value, catChange,
    {
      deep: true
    }
  );
</script>

<template>
  <div class="flex flex-col md:flex-row content-between dark:bg-slate-500">
    <PrimaryButton
      class="m-4"
      type="button"
      @click="addCategory"
    >
      Add an Existing Cat
    </PrimaryButton>
    <PrimaryButton
      class="m-4"
      type="button"
      @click="createANewCategory"
    >
      Create a New Cat
    </PrimaryButton>
  </div>

  <InputError :message="percentError" />
  <div class="flex flex-col md:flex-row content-between dark:bg-slate-500">
    <div
      class="flex flex-wrap bg-slate-500 mr-4"
      :class="{'border border-red-600 dark:border-red-400': percentError}"
    >
      <div
        v-for="(category, i) in catsRef"
        :key="i"
        class="m-2"
      >
        <template v-if="category.cat_data.cat_id">
          <InputLabel
            :for="getUuid('category-select', i)"
            value="Category"
          />

          <div :style="catSelectBorder(category)">
            <Multiselect
              :id="getUuid('category-select', i)"
              class="my-multiselect"
              v-model="catsRef[i].cat_data"
              track-by="cat_id"
              label="name"
              placeholder="Select a Category"
              deselect-label=""
              group-label="category_type"
              group-values="categories"
              :group-select="false"
              select-label=""
              :options="fetchFilteredCatsOptions(category)"
              :allow-empty="false"
              :searchable="true"
            />
            <!-- @keyup.prevent.stop="preventBackspaceNavigation" -->
          </div>
        </template>

        <template v-else>
          <h2 class="text-xl">
            Create a New Category
          </h2>
          <CategoryInputs
            :category-types="categoryTypes"
            :errors="getError(i)"
            @field-update="(data) => createCategory(i, data)"
          />
        </template>

        <InputLabel
          :for="getUuid('category-percent', i)"
          value="Percentage of Transaction Total"
          class="mt-2"
        />
        <input
          :id="getUuid('category-percent', i)"
          type="number"
          min=".1"
          step=".001"
          v-model="catsRef[i].percent"
          class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block  p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
          :style="catSelectBorder(category)"
        >
        <DangerButton
          class="max-h-1 max-w-1"
          type="button"
          @click="removeCategory(i)"
        >
          Remove
        </DangerButton>
      </div>
    </div>
  </div>
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
