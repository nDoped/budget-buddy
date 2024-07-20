/* global expect */
/* global beforeEach */
/* global test */
import {mount} from "@vue/test-utils";
import TransactionCategory from '@/Components/TransactionCategory.vue';
import ToggleSlider from '@/Components/ToggleSlider.vue';
import CategoryInputs from '@/Components/CategoryInputs.vue';
let wrapper;

const props = {
  totalAmount: null,
  categories: [],
  categoryTypes: [
    {
      id: 1,
      name: "Food",
      hex_color: "#111111"
    },
    {
      id: 2,
      name: "Vehicle",
      hex_color: "#222222"
    },
  ],
  availableCategories: [
    {
      cat_id: 1,
      name: "Eggs",
      cat_type_id: 1,
      cat_type_name: "Food",
      hex_color: "#FF0000"
    },
    {
      cat_id: 2,
      name: "Bacon",
      cat_type_id: 1,
      cat_type_name: "Food",
      hex_color: "#FF3546"
    }
  ],
  errors: {}
};
let addCatBtnId = null;
let createCatBtnId = null;
let addLineItemBttnId = null;
let calcPercentagesButtonId = null;
let taxElId = null;
beforeEach(() => {
  wrapper = mount(TransactionCategory,
    {
      props: props
    }
  );
  const uuid = wrapper.vm.uuid;
  addCatBtnId = `add-cat-button-0-${uuid}`;
  taxElId = `tax-amount-0-${uuid}`;
  createCatBtnId = `create-cat-button-0-${uuid}`;
  addLineItemBttnId = `add-line-item-button-0-${uuid}`;
  calcPercentagesButtonId = `calculate-percentages-button-0-${uuid}`;
});

test("test that ToggleSlider functions", async () => {
  expect(wrapper.vm.calcCatsByReciept).toBe(false);
  // should not show the tax input
  expect(wrapper.find("#" + taxElId).exists()).toBe(false);
  expect(wrapper.find("#" + addCatBtnId).exists()).toBe(true);
  expect(wrapper.find("#" + createCatBtnId).exists()).toBe(true);

  const toggle = wrapper.getComponent(ToggleSlider);
  expect(toggle.text()).toBe("Enter receipt line items");

  await toggle.vm.$emit('update:modelValue', true);

  expect(wrapper.vm.calcCatsByReciept).toBe(true);
  expect(wrapper.find("#" + taxElId).exists()).toBe(true);
  expect(wrapper.find("#" + addCatBtnId).exists()).toBe(false);
  expect(wrapper.find("#" + createCatBtnId).exists()).toBe(false);
});

test("test receipt line items with two items in the same cat", async () => {
  expect(wrapper.vm.calcCatsByReciept).toBe(false);
  // should not show the tax input
  expect(wrapper.find("#" + taxElId).exists()).toBe(false);
  // should show the add cat and create cat buttons
  expect(wrapper.find("#" + addCatBtnId).exists()).toBe(true);
  expect(wrapper.find("#" + createCatBtnId).exists()).toBe(true);

  const toggle = wrapper.getComponent(ToggleSlider);
  expect(toggle.text()).toBe("Enter receipt line items");

  await toggle.vm.$emit('update:modelValue', true);

  expect(wrapper.vm.calcCatsByReciept).toBe(true);
  expect(wrapper.find("#" + addCatBtnId).exists()).toBe(false);
  expect(wrapper.find("#" + createCatBtnId).exists()).toBe(false);
  expect(wrapper.text()).toContain('Please enter the transaction total and tax paid');
  const taxEl = wrapper.get("#" + taxElId);
  await taxEl.setValue(10);
  expect(wrapper.text()).toContain('Please enter the transaction total');
  await wrapper.setProps({ totalAmount: 90 });
  await taxEl.setValue(null);
  expect(wrapper.text()).toContain('Please enter the tax paid');
  await taxEl.setValue(10);
  const addLineItemBttn = wrapper.get("#" + addLineItemBttnId);
  await addLineItemBttn.trigger('click');
  let expectedLineItems = [
    {
      cat_data: props.availableCategories[0],
      price: null
    }
  ];
  expect(wrapper.vm.lineItems).toEqual(expectedLineItems);
  await addLineItemBttn.trigger('click');
  expectedLineItems = [
    {
      cat_data: props.availableCategories[0],
      price: null
    },
    {
      cat_data: props.availableCategories[0],
      price: null
    }
  ];
  expect(wrapper.vm.lineItems).toEqual(expectedLineItems);
  expect(wrapper.vm.showCalcPercentBtn).toBe(false);
  const lineItem0PriceInputId = `line-item-amount-0-${wrapper.vm.uuid}`;
  await wrapper.get('#' + lineItem0PriceInputId).setValue(45);
  const lineItem1PriceInputId = `line-item-amount-1-${wrapper.vm.uuid}`;
  await wrapper.get('#' + lineItem1PriceInputId).setValue(45);
  expect(wrapper.text()).toContain('Line items must sum to the total amount minus tax. You are over by $10');
  expect(wrapper.vm.showCalcPercentBtn).toBe(false);
  await wrapper.get('#' + lineItem1PriceInputId).setValue(25);
  expect(wrapper.text()).toContain('Line items must sum to the total amount minus tax. You are under by $10');
  await wrapper.get('#' + lineItem1PriceInputId).setValue(35);
  expect(wrapper.vm.catsRef).toEqual([]);
  const calcPercentagesButton = wrapper.get("#" + calcPercentagesButtonId);
  await calcPercentagesButton.trigger('click');
  let expectedCatsRef = [
    {
      cat_data: props.availableCategories[0],
      percent: 100
    },
  ];
  expect(wrapper.vm.catsRef).toEqual(expectedCatsRef);
});

test("test receipt line items with two items in different cats", async () => {
  await wrapper.setProps({ totalAmount: 100 });
  expect(wrapper.vm.calcCatsByReciept).toBe(false);
  // should not show the tax input
  expect(wrapper.find("#" + taxElId).exists()).toBe(false);
  expect(wrapper.find("#" + addCatBtnId).exists()).toBe(true);
  expect(wrapper.find("#" + createCatBtnId).exists()).toBe(true);

  const toggle = wrapper.getComponent(ToggleSlider);
  expect(toggle.text()).toBe("Enter receipt line items");

  await toggle.vm.$emit('update:modelValue', true);

  expect(wrapper.vm.calcCatsByReciept).toBe(true);
  expect(wrapper.find("#" + addCatBtnId).exists()).toBe(false);
  expect(wrapper.find("#" + createCatBtnId).exists()).toBe(false);
  const taxEl = wrapper.get("#" + taxElId);
  await taxEl.setValue(10);
  const addLineItemBttn = wrapper.get("#" + addLineItemBttnId);
  await addLineItemBttn.trigger('click');
  let expectedLineItems = [
    {
      cat_data: props.availableCategories[0],
      price: null
    }
  ];
  expect(wrapper.vm.lineItems).toEqual(expectedLineItems);
  await addLineItemBttn.trigger('click');
  wrapper.vm.lineItems[1] = {
      cat_data: props.availableCategories[1],
      price: null
  };
  expectedLineItems = [
    {
      cat_data: props.availableCategories[0],
      price: null
    },
    {
      cat_data: props.availableCategories[1],
      price: null
    }
  ];
  expect(wrapper.vm.lineItems).toEqual(expectedLineItems);
  expect(wrapper.vm.showCalcPercentBtn).toBe(false);
  const lineItem0PriceInputId = `line-item-amount-0-${wrapper.vm.uuid}`;
  await wrapper.get('#' + lineItem0PriceInputId).setValue(23.34);
  const lineItem1PriceInputId = `line-item-amount-1-${wrapper.vm.uuid}`;
  await wrapper.get('#' + lineItem1PriceInputId).setValue(90 - 23.34);
  expect(wrapper.vm.showCalcPercentBtn).toBe(true);
  expect(wrapper.vm.catsRef).toEqual([]);
  const calcPercentagesButton = wrapper.get("#" + calcPercentagesButtonId);
  await calcPercentagesButton.trigger('click');
  let expectedCatsRef = [
    {
      cat_data: props.availableCategories[0],
      percent: 25.93 // 23.34 / 90 = 0.2593333
    },
    {
      cat_data: props.availableCategories[1],
      percent: 74.07 // (90 - 23.34) / 90 = 0.7406666
    },
  ];
  expect(wrapper.vm.catsRef).toEqual(expectedCatsRef);
});

test("test Add an Existing Cat button", async () => {
  expect(wrapper.vm.catsRef).toEqual([]);
  expect(wrapper.vm.filteredCats).toEqual(props.availableCategories);
  const addCatBtn = wrapper.get("#" + addCatBtnId);
  await addCatBtn.trigger('click');
  let expectedCatsRef = [
    {
      cat_data: props.availableCategories[0],
      percent: 0
    }
  ];
  expect(wrapper.vm.filteredCats).toEqual([ props.availableCategories[1] ]);
  expect(wrapper.vm.catsRef).toEqual(expectedCatsRef);
});

test("test Add an Existing Cat button with percentages", async () => {
  expect(wrapper.vm.catsRef).toEqual([]);
  const addCatBtn = wrapper.get("#" + addCatBtnId);
  await addCatBtn.trigger('click');
  let expectedCatsRef = [
    {
      cat_data: props.availableCategories[0],
      percent: 0
    }
  ];
  expect(wrapper.vm.catsRef).toEqual(expectedCatsRef);
  const percentInputId = `category-percent-0-${wrapper.vm.uuid}`;
  const percentInput = wrapper.get("#" + percentInputId);
  await percentInput.setValue(10);
  expectedCatsRef = [
    {
      cat_data: props.availableCategories[0],
      percent: 10
    }
  ];
  expect(wrapper.vm.catsRef).toEqual(expectedCatsRef);
  await addCatBtn.trigger('click');
  expectedCatsRef = [
    {
      cat_data: props.availableCategories[0],
      percent: 10
    },
    {
      cat_data: props.availableCategories[1],
      percent: 0
    }
  ];
  expect(wrapper.vm.catsRef).toEqual(expectedCatsRef);
});

test("test Create New Cat button", async () => {
  expect(wrapper.vm.catsRef).toEqual([]);
  const createCatBtn = wrapper.get("#" + createCatBtnId);
  await createCatBtn.trigger('click');
  let expectedCatsRef = [
    {
      cat_data: {
        cat_id: null,
        name: null,
        cat_type_id: null,
        cat_type_name: null,
        hex_color: "#000000"
      },
      percent: 0
    }
  ];
  expect(wrapper.vm.catsRef).toEqual(expectedCatsRef);
  const categoryInputs = wrapper.getComponent(CategoryInputs);
  const eventPayload = {
    name: "New Cat",
    type: 2,
    hex_color: "#444444"
  };
  await categoryInputs.vm.$emit('fieldUpdate', eventPayload);
  expectedCatsRef = [
    {
      cat_data: {
        cat_id: null,
        name: eventPayload.name,
        cat_type_id: eventPayload.type,
        cat_type_name: null,
        hex_color: eventPayload.hex_color
      },
      percent: 0
    }
  ];
  expect(wrapper.vm.catsRef).toEqual(expectedCatsRef);
});

test("test Create New Cat button when appending to catsRef", async () => {
  expect(wrapper.vm.catsRef).toEqual([]);
  const addCatBtn = wrapper.get("#" + addCatBtnId);
  await addCatBtn.trigger('click');
  let expectedCatsRef = [
    {
      cat_data: props.availableCategories[0],
      percent: 0
    }
  ];
  expect(wrapper.vm.catsRef).toEqual(expectedCatsRef);
  const createCatBtn = wrapper.get("#" + createCatBtnId);
  await createCatBtn.trigger('click');

  expectedCatsRef = [
    {
      cat_data: props.availableCategories[0],
      percent: 0
    },
    {
      cat_data: {
        cat_id: null,
        name: null,
        cat_type_id: null,
        cat_type_name: null,
        hex_color: "#000000"
      },
      percent: 0
    }
  ];
  expect(wrapper.vm.catsRef).toEqual(expectedCatsRef);
  const categoryInputs = wrapper.getComponent(CategoryInputs);
  const eventPayload = {
    name: "New Cat",
    type: 2,
    hex_color: "#444444"
  };
  await categoryInputs.vm.$emit('fieldUpdate', eventPayload);
  expectedCatsRef = [
    {
      cat_data: props.availableCategories[0],
      percent: 0
    },
    {
      cat_data: {
        cat_id: null,
        name: eventPayload.name,
        cat_type_id: eventPayload.type,
        cat_type_name: null,
        hex_color: eventPayload.hex_color
      },
      percent: 0
    }
  ];
  expect(wrapper.vm.catsRef).toEqual(expectedCatsRef);
});

test("test catsRef when props.categories is not empty", async () => {
  let newProps = {
    ...props,
    categories: [
      {
        cat_data: {
          cat_id: 1,
          name: "Eggs",
          cat_type_id: 1,
          cat_type_name: "Food",
          hex_color: "#FF0000",
        },
        percent: 50

      },
      {
        cat_data: {
          cat_id: 2,
          name: "Bacon",
          cat_type_id: 1,
          cat_type_name: "Food",
          hex_color: "#FF3546",
        },
        percent: 50
      }
    ]
  };
  const wrapper = mount(TransactionCategory, {
    props: newProps
  })
  expect(wrapper.vm.catsRef).toEqual(newProps.categories);
  expect(wrapper.vm.filteredCats).toEqual([]);
});
