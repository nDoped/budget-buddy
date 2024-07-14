
import {mount} from "@vue/test-utils";
import ToggleSlider from '@/Components/ToggleSlider.vue';
// import { expect, test } from "vitest";
let wrapper;

beforeEach(() => {
  wrapper = mount(ToggleSlider,
    {
      props: {
        label: "Activate me",
        modelValue: false
      }
    }
  );
});

it("test default for label prop", async () => {
  expect(ToggleSlider.props.label.default).toContain("Turn it on");
});

test("test it has a input checkbox", () => {
  expect(wrapper.find("input[type=checkbox]").exists()).toBe(true);
});

it("testing label uses prop", async () => {
  expect(wrapper.find("label").exists()).toBe(true);
  expect(wrapper.find("label").text()).toBe("Activate me");
});


test('modelValue should be updated', async () => {
  const wrapper = mount(ToggleSlider, {
    attachTo: document.body,
    props: {
      modelValue: false,
      'onUpdate:modelValue': (e) => wrapper.setProps({ modelValue: e })
    }
  })
  expect(wrapper.props('modelValue')).toBe(false)
  const ac = await wrapper.get("input").setChecked(true);
  expect(wrapper.props('modelValue')).toBe(true)
})
