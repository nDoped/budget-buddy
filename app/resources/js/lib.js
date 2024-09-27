  import {
    nextTick,
  } from 'vue';

export const forceNumericalInput = (evt) => {
  if (! evt.key.match(/^[0-9.]*$/)) {
    evt.preventDefault();
  }
};

export const forceMonetaryInput = (event, allowNegativeValues = false, currencyCode = "USD") => {
  const currencyRegexs = {
    USD: {
      full: /^-?[,0-9]*\.[0-9]{2}$/,
      allowedSymbols: /^[-,.0-9]*$/,
    }
  };
  const target = event.target;
  const key = event.key;
  if (!currencyRegexs[currencyCode]) {
    console.error(`Currency code ${currencyCode} not supported`);
    return;
  }
  if (! key.match(currencyRegexs[currencyCode].allowedSymbols)) {
    event.preventDefault();

  } else {
    // only allow one decimal point. With input type=number,
    // the first demical key press will not register until
    // the next numerical key is pressed. So we must store the
    // state to block multiple .'s in a row
    const lastKeyPress = localStorage.getItem('lastKeyPress');
    if (key === '.'
      && (target.value.includes('.') || lastKeyPress === '.')
    ) {
      event.preventDefault();

    // negative sign can only be first character
    } else if (key === '-'
      && (target.value || ! allowNegativeValues)
    ) {
      event.preventDefault();

    // only allow two decimal places
    } else if (target.value.match(currencyRegexs[currencyCode].full)) {
      // This only works with input type=text... i tried some
      // stuff... it didn't work. Ideally, we want to block from entering
      // any more decimal places but still allow edits to the whole
      // number.

      // We have two decimals, so only block if the user is trying to
      // to add a thousandths place... ie. allow edits to the whole value
      // target.setAttribute('type', 'text');
      // const decimalPoint = target.value.length - 2;
      // if (event.target.selectionStart >= decimalPoint) {
      //   target.setSelectionRange(decimalPoint, decimalPoint);
      //   target.setAttribute('type', 'number');
      //   event.preventDefault();
      // }
    }
    localStorage.setItem("lastKeyPress", key);
  }
};

export  const focusElement = (id, select = false) => {
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
