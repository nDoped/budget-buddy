function onEdit(e) {
  if (e.source.getActiveSheet().getName() === 'Budget') {
    if (e.range.getA1Notation() === 'A2') {
      refreshMonthlyGraph();
    } else if (e.range.getA1Notation() === 'F2' || e.range.getA1Notation() === 'H2') {
      refreshAnnualGraph();
      fetchNetGrowthVals();
    }
  } else {
    refreshMonthlyGraph();
    fetchNetGrowthVals();
    refreshAnnualGraph();
  }
  /*
  let budgetSheet =  SpreadsheetApp.getActive().getSheetByName("Budget");
  let charts = budgetSheet.getCharts();
  for (var i in charts) {
    budgetSheet.removeChart(charts[i]);
  }
  */
}

function refreshMonthlyGraph() {
  let budgetSheet =  SpreadsheetApp.getActive().getSheetByName("Budget");
  let dataSheet =  SpreadsheetApp.getActive().getSheetByName("calc_data");
  let selectedMonth = budgetSheet.getRange(2, 1).getValue();
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

function refreshAnnualGraph() {
  let budgetSheet =  SpreadsheetApp.getActive().getSheetByName("Budget");
  let dataSheet =  SpreadsheetApp.getActive().getSheetByName("calc_data");
  let monthlyNetIncomes = [];
  let annualNetSnapshotIncomes =[];
  let annualNet = 0;
  fetchRangeMonths().forEach(m => {
    let sheet = SpreadsheetApp.getActive().getSheetByName(m);
    if (sheet === null) {
      return;
    }

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
    let monthlyNet = monthlyExpendable + mainSavingsMonthlyNetGrowth + kifaruSavingsMonthlyNetGrowth;
    annualNet += monthlyNet;
    monthlyNetIncomes.push(monthlyNet);
    annualNetSnapshotIncomes.push(annualNet);
  });

  dataSheet.getRange("A2:C").clearContent();
  let row = 0;
  fetchRangeMonths().forEach(m => {
    let monthCell = dataSheet.getRange(row + 2, 1);
    monthCell.setValue(m);
    let monthlyNetIncomesCol = dataSheet.getRange(row + 2, 2);
    monthlyNetIncomesCol.setValue(monthlyNetIncomes[row]);
    let annualNetSnapShotIncomesCol = dataSheet.getRange(row + 2, 3);
    annualNetSnapShotIncomesCol.setValue(annualNetSnapshotIncomes[row]);

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
          .addRange(dataSheet.getRange("A2:C"))
          .build();
        budgetSheet.updateChart(newChart);
        updatedChart = true;
      }
    }
  }

  if (! updatedChart) {
    let chart = budgetSheet.newChart()
      .setChartType(Charts.ChartType.AREA)
      .addRange(dataSheet.getRange("A2:C"))
      .setPosition(15, 7, 0, 0)
      .setOption('colors', [ 'red', 'yellow' ])
      .setOption('pointSize', 2)
      .setOption("title", "2022 Annual Net Income")
      .setOption('series', {
        1: {

          pointsVisible: false
        },
        2: {
          labelInLegend: true,
        }
      })
      .setOption("legendTextStyle", { color: 'white' })
      .setOption("vAxis.gridlines", {color: '#333', minSpacing: 1000})
      .setOption("vAxis.minorGridlines", {color: '#333', minSpacing: 100})
      .setOption('legend', {position: 'top', textStyle: {color: 'white', fontSize: 11}})
      .build();


    budgetSheet.insertChart(chart);
    let annualGraphIdCell = dataSheet.getRange(1, 6);
    annualGraphIdCell.setValue(chart.getChartId());
  }
}

function fetchPreRangeMonths() {
  let budgetSheet =  SpreadsheetApp.getActive().getSheetByName("Budget");
  let startMonth = budgetSheet.getRange(2, 6).getValue();
  if (startMonth === 'January') {
    return [];
  }

  var months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
  let rangeMonths = [];
  let include = false;
  for (let i = 0; i < months.length; i++) {
    if (months[i] === startMonth) {
      break;
    }
    rangeMonths.push(months[i]);
  }
  return rangeMonths;
}

function fetchRangeMonths() {
  let budgetSheet =  SpreadsheetApp.getActive().getSheetByName("Budget");
  let startMonth = budgetSheet.getRange(2, 6).getValue();
  let endMonth = budgetSheet.getRange(2, 8).getValue();

  var months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
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
  var budgetSheet = SpreadsheetApp.getActive().getSheetByName("Budget");

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


  fetchPreRangeMonths().forEach(m => {
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


  budgetSheet.getRange(4, 7).setValue(dcAnnualBalanceStart);
  budgetSheet.getRange(6, 7).setValue(wfAnnualBalanceStart);
  budgetSheet.getRange(8, 7).setValue(ppAnnualBalanceStart);
  budgetSheet.getRange(10, 7).setValue(mainSavingsAnnualBalanceStart);
  budgetSheet.getRange(12, 7).setValue(kifaruSavingsAnnualBalanceStart);

  budgetSheet.getRange(16, 7).setValue(itAnnualBalanceStart);
  budgetSheet.getRange(18, 7).setValue(ppCreditAnnualBalanceStart);
  budgetSheet.getRange(20, 7).setValue(careCreditAnnualBalanceStart);
  budgetSheet.getRange(22, 7).setValue(studentLoanAnnualBalanceStart);
  budgetSheet.getRange(24, 7).setValue(vehicleLoanAnnualBalanceStart);

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

  fetchRangeMonths().forEach(m => {
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
  });


  budgetSheet.getRange(4, 8).setValue(dcAnnualEndRangeGrowth);
  budgetSheet.getRange(6, 8).setValue(wfAnnualEndRangeGrowth);
  budgetSheet.getRange(8, 8).setValue(ppAnnualEndRangeGrowth);
  budgetSheet.getRange(10, 8).setValue(mainSavingsAnnualEndRangeGrowth);
  budgetSheet.getRange(12, 8).setValue(kifaruSavingsAnnualEndRangeGrowth);

  budgetSheet.getRange(16, 8).setValue(itAnnualEndRangeGrowth);
  budgetSheet.getRange(18, 8).setValue(ppCreditAnnualEndRangeGrowth);
  budgetSheet.getRange(20, 8).setValue(careCreditAnnualEndRangeGrowth);
  budgetSheet.getRange(22, 8).setValue(studentLoanAnnualEndRangeGrowth);
  budgetSheet.getRange(24, 8).setValue(vehicleLoanAnnualEndRangeGrowth);
}

function fetchMonthlyAccountExpenses(sheet) {
  let monthlyExpenses = {};
  monthlyExpenses.dc = sheet.getRange(3, 11).getValue();
  monthlyExpenses.wf = sheet.getRange(4, 11).getValue();
  monthlyExpenses.pp = sheet.getRange(5, 11).getValue();
  monthlyExpenses.mainSavings = sheet.getRange(6, 11).getValue();
  monthlyExpenses.kifaruSavings = sheet.getRange(7, 11).getValue();

  monthlyExpenses.it = sheet.getRange(3, 15).getValue();
  monthlyExpenses.ppCredit = sheet.getRange(4, 15).getValue();
  monthlyExpenses.careCredit = sheet.getRange(5, 15).getValue();
  monthlyExpenses.studentLoan = sheet.getRange(6, 15).getValue();
  monthlyExpenses.vehicleLoan = sheet.getRange(7, 15).getValue();
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
