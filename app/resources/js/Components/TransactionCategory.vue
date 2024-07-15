<script setup>
  import {
    toRaw,
    ref,
    watch,
    nextTick,
    computed
  } from 'vue';
  import InputLabel from '@/Components/InputLabel.vue';
  import InputError from '@/Components/InputError.vue';
  import CategoryInputs from '@/Components/CategoryInputs.vue';
  import CategorySelect from '@/Components/CategorySelect.vue';
  import ToggleSlider from '@/Components/ToggleSlider.vue';
  import PrimaryButton from '@/Components/PrimaryButton.vue';
  import DangerButton from '@/Components/DangerButton.vue';

  const emit = defineEmits(['category-update', 'invalid-category-state']);
  const props = defineProps({
    totalAmount: {
      type: Number,
      default: 0
    },
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

  const calcCatsByReciept = ref(false);

  const filteredCats = computed(() => {
    return props.availableCategories.filter((ac) => {
      return ! catsRef.value.find(cr => cr.cat_data.cat_id == ac.cat_id);
    });
  });

  /*
   * Receipt logic
   */
  const taxAmount = ref(null);
  const lineItems = ref([]);
  const showCalcPercentBtn = computed(() => {
    return lineItems.value.length > 0 && (lineItemSum.value === (props.totalAmount - taxAmount.value));
  });
  const lineItemSum = computed(() => {
    return lineItems.value.reduce((acc, item) => {
      return acc + item.price;
    }, 0);
  });
  const calculatePercentages = () => {
    let subTotal = lineItemSum.value;
    catsRef.value = [];

    let catTotals = {
      new: []
    };
    lineItems.value.forEach((li) => {
      if (! li.cat_data.cat_id) {
        catTotals.new.push({
          sub_total: li.price,
          cat_data: li.cat_data,
        });
      } else {
        if (! catTotals[li.cat_data.cat_id]) {
          catTotals[li.cat_data.cat_id] = {
            sub_total: li.price,
            cat_data: li.cat_data,
          };
        } else {
          catTotals[li.cat_data.cat_id].sub_total += li.price;
        }
      }
    });
    for (let catId in catTotals) {
      if (catId === 'new') {
        continue;
      }
      let catSubTotal = catTotals[catId].sub_total;
      let catData = catTotals[catId].cat_data;

      catsRef.value.push({
        "cat_data": {
          cat_id: catData.cat_id,
          name: catData.name,
          cat_type_id: catData.cat_type_id,
          cat_type_name: catData.cat_type_name,
          hex_color: catData.hex_color,
        },
        percent: Math.round(((catSubTotal / subTotal) * 100 + Number.EPSILON) * 100) / 100,
      });
    }
    // add the to be created cats
    catTotals.new.forEach((c) => {
      catsRef.value.push({
        "cat_data": {
          cat_id: c.cat_data.cat_id,
          name: c.cat_data.name,
          cat_type_id: c.cat_data.cat_type_id,
          cat_type_name: c.cat_data.cat_type_name,
          hex_color: c.cat_data.hex_color,
        },
        percent: Math.round(((c.sub_total / subTotal) * 100 + Number.EPSILON) * 100) / 100,
      });
    });
    calcCatsByReciept.value = false;
  };
  const addLineItem = () => {
    let lastEnteredCatIndex = 0;
    if (lineItems.value.length > 0) {
      lastEnteredCatIndex = props.availableCategories.findIndex((c) => c.cat_id === lineItems.value[lineItems.value.length - 1].cat_data.cat_id);
    }
    if (lastEnteredCatIndex < 0) {
      lastEnteredCatIndex = 0;
    }
    lineItems.value.push({
      "cat_data": {
        cat_id: props.availableCategories[lastEnteredCatIndex].cat_id,
        name: props.availableCategories[lastEnteredCatIndex].name,
        cat_type_id: props.availableCategories[lastEnteredCatIndex].cat_type_id,
        cat_type_name: props.availableCategories[lastEnteredCatIndex].cat_type_name,
        hex_color: props.availableCategories[lastEnteredCatIndex].hex_color,
      },
      price: null,
    });
    focusElement(getUuid('line-item-amount', lineItems.value.length - 1));
  };
  const removeLineItem = (i) => {
    lineItems.value.splice(i, 1);
  };
  const createLineItemCategory = (i, data) => {
    lineItems.value[i].cat_data.name = data.name;
    lineItems.value[i].cat_data.hex_color = data.hex_color;
    lineItems.value[i].cat_data.cat_type_id = data.type;
  };
  const createANewLineItemCategory = () => {
    lineItems.value.push({
      "cat_data" : {
        cat_id: null,
        name: null,
        cat_type_id: null,
        cat_type_name: null,
        hex_color: '#000000',
      },
      price: null,
    });
  };

  /*
   * Percent logic
   */
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
    if (props.errors[`categories.${i}.cat_data.name`]) {
      return {name: props.errors[`categories.${i}.cat_data.name`]};
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
    return `border: solid ${cat.cat_data.hex_color}; border-radius: 5px;`;
  };
  const uuid = crypto.randomUUID();
  const getUuid = (el, i = 0) => {
    return `${el}-${i}-${uuid}`;
  };
  const catChange = () => {
    if (percentTotal.value === 100 || catsRef.value.length === 0) {
      emit('category-update', catsRef);
    } else {
      emit('invalid-category-state');
    }
  };

  const catSelected = (catRefId) => {
    focusElement(getUuid('category-percent', catRefId), true);
  };

  const removeCategory = (i) => {
    catsRef.value.splice(i, 1);
  };
  const focusElement = (id, select = false) => {
    nextTick(() => {
      const element = document.getElementById(id);
      if (element) {
        element.focus();
        if (select) {
          element.select();
        }
      }
    });
  };
  const addCategory = () => {
    catsRef.value.push({
      "cat_data": {
        cat_id: filteredCats.value[0].cat_id,
        name: filteredCats.value[0].name,
        cat_type_id: filteredCats.value[0].cat_type_id,
        cat_type_name: filteredCats.value[0].cat_type_name,
        hex_color: filteredCats.value[0].hex_color,
      },
      percent: 0,
    });
    focusElement(getUuid('category-select', catsRef.value.length - 1));
  };
  const createCategory = (i, data) => {
    catsRef.value[i].cat_data.name = data.name;
    catsRef.value[i].cat_data.hex_color = data.hex_color;
    catsRef.value[i].cat_data.cat_type_id = data.type;
  };
  const createANewCategory = () => {
    catsRef.value.push({
      "cat_data" : {
        cat_id: null,
        name: null,
        cat_type_id: null,
        cat_type_name: null,
        hex_color: '#000000',
      },
      percent: 0,
    });
  };
  watch(
    catsRef, catChange,
    {
      deep: true
    }
  );
</script>

<template>
  <div>
    <ToggleSlider
      v-model="calcCatsByReciept"
      label="Enter receipt line items"
    />

    <template v-if="! calcCatsByReciept">
      <div class="flex flex-col md:flex-row content-between dark:bg-slate-500">
        <PrimaryButton
          :id="getUuid('add-cat-button')"
          class="m-4"
          type="button"
          @click="addCategory"
        >
          Add an Existing Cat
        </PrimaryButton>
        <PrimaryButton
          :id="getUuid('create-cat-button')"
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
              <CategorySelect
                :select-id="getUuid('category-select', i)"
                :available-categories="filteredCats"
                v-model="catsRef[i].cat_data"
                @update:model-value="catSelected(i)"
              />
            </template>

            <template v-else>
              <h2 class="text-xl">
                Create a New Category
              </h2>
              <CategoryInputs
                :id="getUuid('category-inputs', i)"
                :type="category.cat_data.cat_type_id"
                :name="category.cat_data.name"
                :color="category.cat_data.hex_color"
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
              step=".01"
              v-model="catsRef[i].percent"
              class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block  p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
              :style="catSelectBorder(category)"
            >
            <DangerButton
              class="max-h-1 "
              type="button"
              @click="removeCategory(i)"
            >
              Remove
            </DangerButton>
          </div>
        </div>
      </div>
    </template>

    <template v-else>
      <div class="flex flex-col md:flex-row content-between dark:bg-slate-500">
        <div class="m-4">
          <InputLabel
            :for="getUuid('tax-amount')"
            value="Tax"
            class="mt-2"
          />
          <input
            :id="getUuid('tax-amount')"
            type="number"
            min=".1"
            step=".01"
            v-model="taxAmount"
            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block  p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
          >
        </div>
        <div v-if="! totalAmount || ! taxAmount">
          <p class="m-4">
            Please enter a total amount and tax amount
          </p>
        </div>
        <template v-else>
          <div class="m-4">
            <PrimaryButton
              :id="getUuid('add-line-item-button')"
              class="m-4"
              type="button"
              @click="addLineItem"
            >
              Add a line item
            </PrimaryButton>

            <PrimaryButton
              class="m-4"
              type="button"
              @click="createANewLineItemCategory"
            >
              Add a line item and create a new category
            </PrimaryButton>
            <PrimaryButton
              :id="getUuid('calculate-percentages-button')"
              v-if="showCalcPercentBtn"
              class="m-4"
              type="button"
              @click="calculatePercentages"
            >
              Calculate Percentages
            </PrimaryButton>
          </div>
        </template>
      </div>

      <InputError :message="percentError" />
      <div class="flex flex-col md:flex-row content-between dark:bg-slate-500">
        <div
          class="flex flex-wrap bg-slate-500 mr-4"
          :class="{'border border-red-600 dark:border-red-400': percentError}"
        >
          <div
            v-for="(item, i) in lineItems"
            :key="i"
            class="m-2"
          >
            <InputLabel
              :for="getUuid('line-item-amount', i)"
              value="Price"
              class="mt-2"
            />
            <input
              :id="getUuid('line-item-amount', i)"
              type="number"
              min=".1"
              step=".01"
              v-model="lineItems[i].price"
              class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block  p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
              :style="catSelectBorder(item)"
            >
            <template v-if="item.cat_data.cat_id">
              <CategorySelect
                :select-id="getUuid('category-select', i)"
                :available-categories="availableCategories"
                v-model="lineItems[i].cat_data"
              />
            </template>

            <template v-else>
              <h2 class="text-xl">
                Create a New Category
              </h2>
              <CategoryInputs
                :id="getUuid('category-inputs', i)"
                :type="item.cat_data.cat_type_id"
                :name="item.cat_data.name"
                :color="item.cat_data.hex_color"
                :category-types="categoryTypes"
                :errors="getError(i)"
                @field-update="(data) => createLineItemCategory(i, data)"
              />
            </template>

            <DangerButton
              class="max-h-1"
              type="button"
              @click="removeLineItem(i)"
            >
              Remove
            </DangerButton>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>
