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

    let dcAnnualNetGrowth = 0;
    let wfAnnualNetGrowth = 0;
    let ppAnnualNetGrowth = 0;
    let mainSavingsAnnualNetGrowth = 0;
    let kifaruSavingsAnnualNetGrowth = 0;

    let itAnnualNetGrowth = 0;
    let ppCreditAnnualNetGrowth = 0;
    let careCreditAnnualNetGrowth = 0;
    let studentLoanAnnualNetGrowth = 0;
    let vehicleLoanAnnualNetGrowth = 0;

    fetchRangeMonths().forEach(m => {
        let sheet = SpreadsheetApp.getActive().getSheetByName(m);

        let monthlyExpenses = fetchMonthlyAccountExpenses(sheet);
        // net expenses...so inverse for assest accounts
        dcAnnualNetGrowth -= monthlyExpenses.dc;
        wfAnnualNetGrowth -= monthlyExpenses.wf;
        ppAnnualNetGrowth -= monthlyExpenses.pp;
        mainSavingsAnnualNetGrowth -= monthlyExpenses.mainSavings;
        kifaruSavingsAnnualNetGrowth -= monthlyExpenses.kifaruSavings;

        itAnnualNetGrowth += monthlyExpenses.it;
        ppCreditAnnualNetGrowth += monthlyExpenses.ppCredit;
        careCreditAnnualNetGrowth += monthlyExpenses.careCredit;
        studentLoanAnnualNetGrowth += monthlyExpenses.studentLoan;
        vehicleLoanAnnualNetGrowth += monthlyExpenses.vehicleLoan;
    });

    budgetSheet.getRange(4, 7).setValue(dcAnnualNetGrowth);
    budgetSheet.getRange(6, 7).setValue(wfAnnualNetGrowth);
    budgetSheet.getRange(8, 7).setValue(ppAnnualNetGrowth);
    budgetSheet.getRange(10, 7).setValue(mainSavingsAnnualNetGrowth);
    budgetSheet.getRange(13, 7).setValue(kifaruSavingsAnnualNetGrowth);

    budgetSheet.getRange(16, 7).setValue(itAnnualNetGrowth);
    budgetSheet.getRange(18, 7).setValue(ppCreditAnnualNetGrowth);
    budgetSheet.getRange(20, 7).setValue(careCreditAnnualNetGrowth);
    budgetSheet.getRange(22, 7).setValue(studentLoanAnnualNetGrowth);
    budgetSheet.getRange(24, 7).setValue(vehicleLoanAnnualNetGrowth);
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
