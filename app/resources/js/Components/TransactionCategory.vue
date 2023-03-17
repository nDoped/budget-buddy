<script setup>
  import {
    toRaw,
    ref,
    watch,
    computed
  } from 'vue';
  import InputLabel from '@/Components/InputLabel.vue';
  import InputError from '@/Components/InputError.vue';
  import PrimaryButton from '@/Components/PrimaryButton.vue';
  import DangerButton from '@/Components/DangerButton.vue';
  import 'vue3-toastify/dist/index.css';

  const emit = defineEmits(['category-update', 'invalid-category-state']);

  const props = defineProps({
    categories: {
      type: Array,
      default: () => []
    },
    availableCategories: {
      type: Array,
      default: () => []
    }
  });

  const filteredCats = computed(() => {
    return props.availableCategories.filter((ac) => {
      return ! catsRef.value.find(cr => cr.cat_id == ac.cat_id);
    });
  });
  const fetchFilteredCatsOptions = (cat) => {
    let ret = [ ...filteredCats.value, cat ];
    ret.sort(function(a, b) {
      if (a.name < b.name) return -1;
      if (a.name > b.name) return 1;
      return 0;
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
    return `border: solid ${cat.color}`;
  };
  const uuid = crypto.randomUUID();
  const getUuid = (el, i) => {
    return `${el}-${i}-${uuid}`;
  };
  const catChange = (e, i) => {
    if (e) {
      let newCatId = e.target.value;
      let newCat = props.availableCategories.find(ac => ac.cat_id === parseInt(newCatId));

      if (catsRef.value[i].name !== newCat.name) {
        catsRef.value[i].name = newCat.name;
      }
      document.getElementById(getUuid('category-select', i)).style.cssText = catSelectBorder(newCat);
      document.getElementById(getUuid('category-percent', i)).style.cssText = catSelectBorder(newCat);
    }

    if (percentTotal.value === 100 || catsRef.value.length === 0) {
      emit('category-update', catsRef);
    } else {
      emit('invalid-category-state');
    }
  };

  const removeCategory = (cat, i) => {
    catsRef.value.splice(i, 1);
    catChange();
  };

  const addCategory = () => {
    catsRef.value.push({
      cat_id: filteredCats.value[0].cat_id,
      name: filteredCats.value[0].name,
      color: filteredCats.value[0].color,
      percent: 100
    });
    catChange();
  };
</script>

<template>
    <div class="m-4 flex flex-row-reverse">
      <PrimaryButton
        type="button"
        @click="addCategory"
      >
        Add a Cat
      </PrimaryButton>
    </div>
    <InputError :message="percentError" />
    <div
      class="flex flex-wrap bg-slate-500"
      :class="{'border border-red-700': percentError}"
    >
      <div
        v-for="(category, i) in catsRef"
        :key="i"
        class="m-4"
      >
        <InputLabel
          :for="getUuid('category-select', i)"
          value="Category"
        />

        <select
          :id="getUuid('category-select', i)"
          v-model="category.cat_id"
          class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
          :style="catSelectBorder(category)"
          @input="catChange($event, i)"
        >
          <option
            v-for="cat in fetchFilteredCatsOptions(category)"
            :key="category + cat.cat_id"
            :value="cat.cat_id"
          >
            {{ cat.name }}
          </option>
        </select>

        <InputLabel
          :for="getUuid('category-percent', i)"
          value="Percentage of Transaction Total"
          class="mt-3"
        />
        <input
          :id="getUuid('category-percent', i)"
          type="number"
          min=".1"
          step=".1"
          v-model="category.percent"
          class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
          :style="catSelectBorder(category)"
          @input="catChange()"
        >
        <DangerButton
          class="max-h-1 max-w-1"
          type="button"
          @click="removeCategory(category, i)"
        >
          Remove
        </DangerButton>
      </div>
    </div>

</template>
