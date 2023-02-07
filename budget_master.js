const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
const groceryColors = { background: "black", font: "white" };
const catColors = {
    AdEnt: { background: "#ead1dc", font: "#c27ba0" },
    Booze: { background: "#85551b", font: "white" },
    Bathroom: { background: "#3c78d8", font: "white" },
    Books: { background: "#ec6868", font: "black" },
    "Groceries:Bread" : groceryColors,
    "Groceries:Butter" : groceryColors,
    "Groceries:Cheese" : groceryColors,
    "Groceries:Chips/Snacks" : groceryColors,
    "Groceries:Condiments" : groceryColors,
    Coffee: { background: "#dcd4c3", font: "white" },
    Default: { background: "#ff00ff", font: "white" },
    "Groceries:Flour" : groceryColors,
    "Groceries:Fruits/Vegetables": groceryColors,
    Gas: { background: "#d9d9d9", font: "black" },
    "Groceries:Generic": groceryColors,
    Hunting: { background: "#3b8d11", font: "white" },
    Isopropyl: { background: "#ffbebe", font: "#c27ba0" },
    "Groceries:Meat": groceryColors,
    "Groceries:Milk/Creme": groceryColors,
    Moving: { background: "#ffe599", font: "black" },
    "Groceries:Nut Butter": groceryColors,
    Puzzles: { background: "#ff9900", font: "#cccccc" },
    "Groceries:Salt/Pepper": groceryColors,
    Rent: { background: "#e41111", font: "white" },
    Restaurant: { background: "#a2c4c9", font: "#666666" },
    Tools: { background: "#999999", font: "#000000" },
    "Vitamins & Minerals": { background: "#a4c2f4", font: "#000000" },
};

var budgetSheet =  SpreadsheetApp.getActive().getSheetByName("Budget");
var dataSheet =  SpreadsheetApp.getActive().getSheetByName("calc_data");

function onEdit(e) {
  budgetSheet.getRange("F35").setValue("Calculating...");
  let activeSheet = e.source.getActiveSheet();
  let activeSheetName = activeSheet.getName();
  if (activeSheetName === 'Budget') {
    if (e.range.getA1Notation() === 'B2') {
      refreshMonthlyGraph();
    } else if (e.range.getA1Notation() === 'G2' || e.range.getA1Notation() === 'I2') {
      refreshAnnualGraphs();
      fetchNetGrowthVals();
    }
  } else {
    if (months.includes(activeSheetName)){
      if (e.range.getA1Notation() === 'J24') {
        fetchCategoryPercentages(activeSheet);
      } else {
        refreshMonthlyBreakdown(activeSheet);
        refreshMonthlyGraph();
        fetchNetGrowthVals();
        refreshAnnualGraphs();
      }
    }
  }
  budgetSheet.getRange("F35").clearContent();
}

function fetchCategoryPercentages(sheet) {
  //let sheet =  SpreadsheetApp.getActive().getSheetByName("January");
  let jsonData = JSON.parse(sheet.getRange('J24').getValue());
  let tempCategories = {};
  let runningTotal = 0;
  let runningTaxableTotal = 0;
  let ret = {};
  for (let i in jsonData.categories) {
    let catSum = jsonData.categories[i].reduce((partialSum, a) => {
      if (typeof a === "boolean") {
        return partialSum;
      } else {
        return partialSum + a;
      }
    }, 0);
    let catIsTaxable = jsonData.categories[i][0];
    tempCategories[i] = {
      sum: catSum,
      taxable: catIsTaxable
    };

    runningTotal += catSum;

    if (catIsTaxable) {
      runningTaxableTotal += catSum;
    }
  }

  if (parseFloat(runningTotal.toFixed(2)) !== parseFloat(jsonData.subTotal)) {
    sheet.getRange('J26').setValue("Totals Error");
    return;
  }

  let taxRate = ((runningTaxableTotal + jsonData.tax) / runningTaxableTotal) - 1;
  if (! isNaN(taxRate) && parseFloat((taxRate * 100).toFixed(1)) !== jsonData.expectedTaxRate && jsonData.tax) {
    sheet.getRange('J26').setValue("Tax rate not expected amount");
    return;
  }
  for (let i in tempCategories) {
    let catSum = tempCategories[i].sum;

    if (! catSum) {
      continue;
    }

    if (tempCategories[i]['taxable']) {
      catSum *= ( 1 + taxRate);
    }

    ret[i] = ((catSum / (jsonData.subTotal + jsonData.tax)) * 100).toFixed(2);
  }

  sheet.getRange('J26').setValue(JSON.stringify(ret));
}

function refreshMonthlyBreakdown(sheet) {
  //let sheet =  SpreadsheetApp.getActive().getSheetByName("January");
  let transData = sheet.getRange("C3:D").getValues();
  let breakdownData = {};

  transData.forEach(t => {
    let amnt = t[0];
    let cat = t[1]
    if (! amnt || cat.match(/^Salary.*|Account Adjustment|^Interest.*|Refund/)) {
      return;
    }
    try {
      cat = JSON.parse(cat);
    } catch (e) {}

    if (typeof cat === 'object') {
      for (let i in cat) {
        if (i in breakdownData) {
          breakdownData[i] += (amnt * cat[i]) / 100;

        } else {
          breakdownData[i] = (amnt * cat[i]) / 100;
        }
      }

    } else {
      if (cat in breakdownData) {
        breakdownData[cat] += amnt;

      } else {
        breakdownData[cat] = amnt;
      }
    }
  });

  let sortedData = sortObj(breakdownData);
  let targetRange = sheet.getRange("P2:Q");
  targetRange.clearContent().setBackgroundColor(null);
  let row = 2;
  for (let cat in sortedData) {
    let catCell = sheet.getRange(row, 16);
    let valCell = sheet.getRange(row, 17);
    catCell.setValue(cat);
    valCell.setValue(sortedData[cat].toFixed(2));
    if (cat in catColors) {
      sheet.getRange(row, 16).setBackgroundColor(catColors[cat].background);
      sheet.getRange(row, 16).setFontColor(catColors[cat].font);
    } else if (cat.match(/^Utility.*$/)) {
      sheet.getRange(row, 16).setBackgroundColor("#20124d");
      sheet.getRange(row, 16).setFontColor("white");
    } else if (cat.match(/^Recurring.*$/)) {
      sheet.getRange(row, 16).setBackgroundColor("#b4a7d6");
      sheet.getRange(row, 16).setFontColor("black");
    } else {
      sheet.getRange(row, 16).setBackgroundColor(catColors["Default"].background);
      sheet.getRange(row, 16).setFontColor(catColors["Default"].font);
    }

    row++;
  }
}

function sortObj(obj) {
  return Object.keys(obj).sort().reduce(function (result, key) {
    result[key] = obj[key];
    return result;
  }, {});
}

function refreshMonthlyGraph() {

  let selectedMonth = budgetSheet.getRange(2, 2).getValue();
  let sheet = SpreadsheetApp.getActive().getSheetByName(selectedMonth);
  let monthlyChartId = dataSheet.getRange(1,7).getValue();
  let updatedChart = false;
  let allVals = sheet.getRange("P2:Q").getValues();
  let i = 1;
  dataSheet.getRange("D1:E").clearContent();

  allVals.forEach(v => {
    if (! v[1] || v[0] === 'Rent') {
      return;
    }
    let nextCatCell = dataSheet.getRange(i, 4);
    let nextValCell = dataSheet.getRange(i, 5);
    nextCatCell.setValue(v[0]);
    nextValCell.setValue(v[1]);
    i++;
  });

  if (monthlyChartId) {
    let charts = budgetSheet.getCharts();
    for (let i in charts) {
      if (charts[i].getChartId() === monthlyChartId) {
        let newChart = charts[i].modify()
          .clearRanges()
          .setOption("title", selectedMonth + " BreakDown")
          .addRange(dataSheet.getRange("D1:E"))
          .build();
        budgetSheet.updateChart(newChart);
        updatedChart = true;
      }
    }
  }

  if (! updatedChart) {
    let chart = budgetSheet.newChart()
      .setChartType(Charts.ChartType.BAR)
      .addRange(dataSheet.getRange("D1:E"))
    //.addRange(filteredInput)
      .setOption("title", selectedMonth + " BreakDown")
      .setPosition(15, 1, 0, 0)
      .build();
    budgetSheet.insertChart(chart);
    let monthlyGraphIdCell = dataSheet.getRange(1, 7);
    monthlyGraphIdCell.setValue(chart.getChartId());
  }
}

function refreshAnnualGraphs() {
  let startMonth = budgetSheet.getRange(2, 7).getValue();
  let endMonth = budgetSheet.getRange(2, 9).getValue();
  let monthlyNetAssetIncomes = [];
  let annualNetAssetSnapshotIncomes =[];
  let annualNetDebtGrowthSnapshots =[];
  let annualAssetNet = 0;
  let annualDebtGrowth = 0;
  let monthlyNetDebtIncomes = [];
  let totalNetGrowths = [];
  let totalMonthlyNetGrowth = 0;
  let pieData = {};

  fetchRangeMonths(startMonth, endMonth).forEach(m => {
    let sheet = SpreadsheetApp.getActive().getSheetByName(m);
    if (sheet === null) {
      return;
    }

    let breakdownData = sheet.getRange("P2:Q").getValues();
    breakdownData.forEach(v => {
      if (! v[1] || v[0].match(/^Utility.*$|^Recurring.*$|^Rent$|Interest/)) {
        return;
      }
      if (v[0] in pieData) {
        pieData[v[0]] += v[1];

      } else {
        pieData[v[0]] = v[1];
      }
    });

    let monthlyExpenses = fetchMonthlyAccountExpenses(sheet);
    // net expenses...inverse for asset accounts
    dcMonthlyNetGrowth = -monthlyExpenses.dc;
    wfMonthlyNetGrowth = -monthlyExpenses.wf;
    ppMonthlyNetGrowth = -monthlyExpenses.pp;
    mainSavingsMonthlyNetGrowth = -monthlyExpenses.mainSavings;
    kifaruSavingsMonthlyNetGrowth = -monthlyExpenses.kifaruSavings;

    itMonthlyNetGrowth = monthlyExpenses.it;
    ppCreditMonthlyNetGrowth = monthlyExpenses.ppCredit;
    careCreditMonthlyNetGrowth = monthlyExpenses.careCredit;
    studentLoanMonthlyNetGrowth = monthlyExpenses.studentLoan;
    vehicleLoanMonthlyNetGrowth = monthlyExpenses.vehicleLoan;

    let monthlyExpendable = dcMonthlyNetGrowth + wfMonthlyNetGrowth + ppMonthlyNetGrowth;
    let monthlyAssetNet = monthlyExpendable + mainSavingsMonthlyNetGrowth + kifaruSavingsMonthlyNetGrowth;
    let monthlyDebtNet =
      itMonthlyNetGrowth
      + ppCreditMonthlyNetGrowth
      + careCreditMonthlyNetGrowth
      + studentLoanMonthlyNetGrowth
      + vehicleLoanMonthlyNetGrowth;

    annualAssetNet += monthlyAssetNet;
    annualDebtGrowth += monthlyDebtNet;
    monthlyNetAssetIncomes.push(monthlyAssetNet);
    monthlyNetDebtIncomes.push(annualDebtGrowth);
    annualNetAssetSnapshotIncomes.push(annualAssetNet);
    annualNetDebtGrowthSnapshots.push(monthlyDebtNet);
    totalMonthlyNetGrowth += monthlyAssetNet - monthlyDebtNet;
    totalNetGrowths.push(totalMonthlyNetGrowth);
  });

  refreshPieGraph(pieData, startMonth, endMonth);

  dataSheet.getRange("H2:M").clearContent();
  let row = 0;
  fetchRangeMonths(startMonth, endMonth).forEach(m => {
    let monthCell = dataSheet.getRange(row + 2, 8);
    monthCell.setValue(m);
    let monthlyNetAssetIncomesCol = dataSheet.getRange(row + 2, 9);
    monthlyNetAssetIncomesCol.setValue(monthlyNetAssetIncomes[row]);
    let annualNetAssetSnapShotIncomesCol = dataSheet.getRange(row + 2, 10);
    annualNetAssetSnapShotIncomesCol.setValue(annualNetAssetSnapshotIncomes[row]);
    let annualNetDebtSnapShotCol = dataSheet.getRange(row + 2, 11);
    annualNetDebtSnapShotCol.setValue(annualNetDebtGrowthSnapshots[row]);
    let monthlyNetDebtIncomesCol = dataSheet.getRange(row + 2, 12);
    monthlyNetDebtIncomesCol.setValue(monthlyNetDebtIncomes[row]);
    let totalNetGrowthCol = dataSheet.getRange(row + 2, 13);
    totalNetGrowthCol.setValue(totalNetGrowths[row]);
    row++;
  });

  let annualChartId = dataSheet.getRange(1,6).getValue();
  let updatedChart = false;
  if (annualChartId) {
    let charts = budgetSheet.getCharts();
    for (let i in charts) {
      if (charts[i].getChartId() === annualChartId) {
        let newChart = charts[i].modify()
          .clearRanges()
          .addRange(dataSheet.getRange("H1:M"))
          .build();
        budgetSheet.updateChart(newChart);
        updatedChart = true;
      }
    }
  }

  if (! updatedChart) {
    let chart = budgetSheet.newChart()
      .setChartType(Charts.ChartType.LINE)
      .addRange(dataSheet.getRange("H1:M"))
      .setPosition(1, 11, 0, 0)
      .setOption('colors', [ 'blue', 'green', 'red', 'yellow' ])
      .setOption("title", "2023 Net Growths")
      .setNumHeaders(1)
      .setOption("height", 554)
      .setOption("width", 924)
      .setOption('vAxis', {
        title: 'Net Growth'
      })
      .build();

    budgetSheet.insertChart(chart);
    let annualGraphIdCell = dataSheet.getRange(1, 6);
    annualGraphIdCell.setValue(chart.getChartId());
  }
}

function refreshPieGraph(pieData, startMonth, endMonth) {
  let pieChartId = dataSheet.getRange(2,6).getValue();
  let updatedChart = false;

  dataSheet.getRange("B1:C").clearContent();

  let i = 1;
  let pieChartColors = [];
  for (let cat in sortObj(pieData)) {

    let amnt = pieData[cat];
    let nextCatCell = dataSheet.getRange(i, 2);
    let nextValCell = dataSheet.getRange(i, 3);
    nextCatCell.setValue(cat);
    nextValCell.setValue(amnt);
    if (cat in catColors) {
      pieChartColors.push(catColors[cat].background);
    } else {
      pieChartColors.push(catColors["Default"].background);
    }
    i++;
  }

  if (pieChartId) {
    let charts = budgetSheet.getCharts();
    for (let i in charts) {
      if (charts[i].getChartId() === pieChartId) {
        budgetSheet.removeChart(charts[i]);
        break;
      }
    }
  }


  let chart = budgetSheet.newChart()
    .setChartType(Charts.ChartType.PIE)
    .addRange(dataSheet.getRange("B1:C"))
    .setOption("title", "Spending BreakDown: " + startMonth + " Through " + endMonth)
    .setOption("colors", pieChartColors)
    .setOption("height", 552)
    .setOption('is3D', true)
    .setOption("width", 924)
    .setPosition(24,11, 0, 0)
    .build();
  budgetSheet.insertChart(chart);
  let pieChartIdCell = dataSheet.getRange(2, 6);
  pieChartIdCell.setValue(chart.getChartId());
}

function fetchPreRangeMonths(startMonth) {
  if (startMonth === 'January') {
    return [];
  }

  let rangeMonths = [];
  for (let i = 0; i < months.length; i++) {
    if (months[i] === startMonth) {
      break;
    }
    rangeMonths.push(months[i]);
  }
  return rangeMonths;
}

function fetchRangeMonths(startMonth, endMonth) {
  let rangeMonths = [];
  let include = false;
  for (let i = 0; i < months.length; i++) {
    if (months[i] === startMonth) {
      include = true;
    }
    if (include) {
      rangeMonths.push(months[i]);
    }
    if (months[i] === endMonth) {
      include = false;
    }
  }
  return rangeMonths;
}

function fetchNetGrowthVals() {
  let startMonth = budgetSheet.getRange(2, 7).getValue();
  let endMonth = budgetSheet.getRange(2, 9).getValue();
  let startBalances = fetchJanFirstBalances();
  // net expenses...so inverse for assest accounts
  let dcAnnualBalanceStart = startBalances.dc;
  let wfAnnualBalanceStart = startBalances.wf;
  let ppAnnualBalanceStart = startBalances.pp;
  let mainSavingsAnnualBalanceStart = startBalances.mainSavings;
  let kifaruSavingsAnnualBalanceStart = startBalances.kifaruSavings;
  let itAnnualBalanceStart = startBalances.it;
  let ppCreditAnnualBalanceStart = startBalances.ppCredit;
  let careCreditAnnualBalanceStart = startBalances.careCredit;
  let studentLoanAnnualBalanceStart = startBalances.studentLoan;
  let vehicleLoanAnnualBalanceStart = startBalances.vehicleLoan;

  fetchPreRangeMonths(startMonth).forEach(m => {
    let monthlyExpenses = {};
    let sheet = SpreadsheetApp.getActive().getSheetByName(m);
    monthlyExpenses = fetchMonthlyAccountExpenses(sheet);

    // net expenses...so inverse for assest accounts
    dcAnnualBalanceStart -= monthlyExpenses.dc;
    wfAnnualBalanceStart -= monthlyExpenses.wf;
    ppAnnualBalanceStart -= monthlyExpenses.pp;
    mainSavingsAnnualBalanceStart -= monthlyExpenses.mainSavings;
    kifaruSavingsAnnualBalanceStart -= monthlyExpenses.kifaruSavings;

    itAnnualBalanceStart += monthlyExpenses.it;
    ppCreditAnnualBalanceStart += monthlyExpenses.ppCredit;
    careCreditAnnualBalanceStart += monthlyExpenses.careCredit;
    studentLoanAnnualBalanceStart += monthlyExpenses.studentLoan;
    vehicleLoanAnnualBalanceStart += monthlyExpenses.vehicleLoan;

  });

  budgetSheet.getRange(4, 8).setValue(dcAnnualBalanceStart);
  budgetSheet.getRange(6, 8).setValue(wfAnnualBalanceStart);
  budgetSheet.getRange(8, 8).setValue(ppAnnualBalanceStart);
  budgetSheet.getRange(10, 8).setValue(mainSavingsAnnualBalanceStart);
  budgetSheet.getRange(12, 8).setValue(kifaruSavingsAnnualBalanceStart);

  budgetSheet.getRange(16, 8).setValue(itAnnualBalanceStart);
  budgetSheet.getRange(18, 8).setValue(ppCreditAnnualBalanceStart);
  budgetSheet.getRange(20, 8).setValue(careCreditAnnualBalanceStart);
  budgetSheet.getRange(22, 8).setValue(studentLoanAnnualBalanceStart);
  budgetSheet.getRange(24, 8).setValue(vehicleLoanAnnualBalanceStart);

  let dcAnnualEndRangeGrowth = 0;
  let wfAnnualEndRangeGrowth = 0;
  let ppAnnualEndRangeGrowth = 0;
  let mainSavingsAnnualEndRangeGrowth = 0;
  let kifaruSavingsAnnualEndRangeGrowth = 0;

  let itAnnualEndRangeGrowth = 0;
  let ppCreditAnnualEndRangeGrowth = 0;
  let careCreditAnnualEndRangeGrowth = 0;
  let studentLoanAnnualEndRangeGrowth = 0;
  let vehicleLoanAnnualEndRangeGrowth = 0;
  let totalExtraExpenses = 0;
  let monthCnt = 0;
  fetchRangeMonths(startMonth, endMonth).forEach(m => {
    let sheet = SpreadsheetApp.getActive().getSheetByName(m);

    let monthlyExpenses = fetchMonthlyAccountExpenses(sheet);
    // net expenses...so inverse for assest accounts
    dcAnnualEndRangeGrowth -= monthlyExpenses.dc;
    wfAnnualEndRangeGrowth -= monthlyExpenses.wf;
    ppAnnualEndRangeGrowth -= monthlyExpenses.pp;
    mainSavingsAnnualEndRangeGrowth -= monthlyExpenses.mainSavings;
    kifaruSavingsAnnualEndRangeGrowth -= monthlyExpenses.kifaruSavings;

    itAnnualEndRangeGrowth += monthlyExpenses.it;
    ppCreditAnnualEndRangeGrowth += monthlyExpenses.ppCredit;
    careCreditAnnualEndRangeGrowth += monthlyExpenses.careCredit;
    studentLoanAnnualEndRangeGrowth += monthlyExpenses.studentLoan;
    vehicleLoanAnnualEndRangeGrowth += monthlyExpenses.vehicleLoan;

    totalExtraExpenses += monthlyExpenses.extraExpenses;
    monthCnt++;
  });

  let avgMonthlyExpenses = totalExtraExpenses / monthCnt;

  budgetSheet.getRange(4, 9).setValue(dcAnnualEndRangeGrowth);
  budgetSheet.getRange(6, 9).setValue(wfAnnualEndRangeGrowth);
  budgetSheet.getRange(8, 9).setValue(ppAnnualEndRangeGrowth);
  budgetSheet.getRange(10, 9).setValue(mainSavingsAnnualEndRangeGrowth);
  budgetSheet.getRange(12, 9).setValue(kifaruSavingsAnnualEndRangeGrowth);

  budgetSheet.getRange(16, 9).setValue(itAnnualEndRangeGrowth);
  budgetSheet.getRange(18, 9).setValue(ppCreditAnnualEndRangeGrowth);
  budgetSheet.getRange(20, 9).setValue(careCreditAnnualEndRangeGrowth);
  budgetSheet.getRange(22, 9).setValue(studentLoanAnnualEndRangeGrowth);
  budgetSheet.getRange(24, 9).setValue(vehicleLoanAnnualEndRangeGrowth);

  budgetSheet.getRange(32, 8).setValue(avgMonthlyExpenses);
}

function fetchMonthlyAccountExpenses(sheet) {
  let monthlyExpenses = {};
  monthlyExpenses.dc = sheet.getRange("U4").getValue();
  monthlyExpenses.wf = sheet.getRange("U5").getValue();
  monthlyExpenses.pp = sheet.getRange("U6").getValue();
  monthlyExpenses.mainSavings = sheet.getRange("U7").getValue();
  monthlyExpenses.kifaruSavings = sheet.getRange("U8").getValue();

  monthlyExpenses.it = sheet.getRange("Y4").getValue();
  monthlyExpenses.ppCredit = sheet.getRange("Y5").getValue();
  monthlyExpenses.careCredit = sheet.getRange("Y6").getValue();
  monthlyExpenses.studentLoan = sheet.getRange("Y7").getValue();
  monthlyExpenses.vehicleLoan = sheet.getRange("Y8").getValue();

  monthlyExpenses.extraExpenses = sheet.getRange("J12").getValue();
  return monthlyExpenses;
}

function fetchJanFirstBalances() {
  let sheet = SpreadsheetApp.getActive().getSheetByName('Jan 1 Balances');
  let accountBalances = {};
  accountBalances.dc = sheet.getRange(4, 2).getValue();
  accountBalances.wf = sheet.getRange(6, 2).getValue();
  accountBalances.pp = sheet.getRange(8, 2).getValue();
  accountBalances.mainSavings = sheet.getRange(10, 2).getValue();
  accountBalances.kifaruSavings = sheet.getRange(12, 2).getValue();

  accountBalances.it = sheet.getRange(4, 4).getValue();
  accountBalances.ppCredit = sheet.getRange(6, 4).getValue();
  accountBalances.careCredit = sheet.getRange(8, 4).getValue();
  accountBalances.studentLoan = sheet.getRange(10, 4).getValue();
  accountBalances.vehicleLoan = sheet.getRange(12, 4).getValue();
  return accountBalances ;
}
