import test from 'node:test';
import assert from 'node:assert/strict';
import {categories,defaultPeriod,financeSummary,financeTransactions,initialExpenses,grouped,inPeriod,paymentMethods,periodError,previousPeriod,serviceOverview,sumAmounts,trendRows,validateExpense,validDate} from '../src/financeData.js';

test('finance fixture reconciles service, payment, daily and monthly summaries',()=>{
 const s=financeSummary(financeTransactions,initialExpenses,defaultPeriod);
 assert.equal(s.count,186);assert.equal(s.revenue,7850000);assert.equal(s.expense,2540000);assert.equal(s.profit,5310000);
 assert.deepEqual(s.services.map(r=>r.count),[62,58,42,18,6]);assert.equal(s.customers,95);
 for(const groups of [s.services,s.payments,s.days,trendRows(s.rows,'day'),trendRows(s.rows,'week'),trendRows(s.rows)]){
  assert.equal(groups.reduce((n,r)=>n+r.total,0),s.revenue);assert.equal(groups.reduce((n,r)=>n+r.count,0),s.count);
 }
 for(const row of s.days)assert.equal(paymentMethods.reduce((n,k)=>n+row[k],0),row.total);
 assert.equal(new Set(financeTransactions.map(r=>r.id)).size,financeTransactions.length);
 assert.ok(financeTransactions.every(r=>r.customer&&validDate(r.date)&&Number.isSafeInteger(r.amount)&&r.amount>0));
 assert.equal(new Set(initialExpenses.map(r=>r.id)).size,initialExpenses.length);
 assert.deepEqual(grouped(s.costs,'category',categories).map(r=>r.total),[1620000,720000,120000,80000]);
 assert.deepEqual(serviceOverview(s.services).at(-1),{label:'Lainnya',count:24,total:900000});
});
test('new and edited expenses change profit only in their selected date range',()=>{
 const added={id:1000,date:'2026-10-06',category:'Operasional',amount:100000,description:'Bahan uji',method:'Tunai'};
 const original=financeSummary(financeTransactions,initialExpenses,defaultPeriod);
 const next=financeSummary(financeTransactions,[...initialExpenses,added],defaultPeriod);
 assert.equal(next.profit,original.profit-100000);assert.equal(next.revenue,original.revenue);
 const edited=financeSummary(financeTransactions,[...initialExpenses,{...added,amount:150000}],defaultPeriod);
 assert.equal(edited.expense,original.expense+150000);
 const moved=financeSummary(financeTransactions,[...initialExpenses,{...added,date:'2026-09-06'}],defaultPeriod);
 assert.equal(moved.expense,original.expense);
 assert.equal(sumAmounts(initialExpenses.filter(r=>inPeriod(r,defaultPeriod))),2540000);
});
test('date filters include boundaries, reject impossible/reversed dates and support empty periods',()=>{
 assert.equal(inPeriod({date:'2026-10-01'},defaultPeriod),true);assert.equal(inPeriod({date:'2026-10-31'},defaultPeriod),true);assert.equal(inPeriod({date:'2026-09-30'},defaultPeriod),false);
 assert.equal(validDate('2026-02-30'),false);assert.equal(validDate('2026-13-01'),false);assert.equal(validDate(''),false);
 assert.ok(periodError({from:'2026-10-07',to:'2026-10-06'}));
 const empty=financeSummary(financeTransactions,initialExpenses,{from:'2025-01-01',to:'2025-01-31'});
 assert.deepEqual([empty.count,empty.revenue,empty.expense,empty.profit,empty.average],[0,0,0,0,0]);
 assert.deepEqual(previousPeriod({preset:'custom',from:'2026-10-04',to:'2026-10-06'}),{from:'2026-10-01',to:'2026-10-03'});
 assert.deepEqual(previousPeriod({preset:'previous',from:'2026-09-01',to:'2026-09-30'}),{from:'2026-08-01',to:'2026-08-31'});
});
test('expense validation accepts whole rupiah and rejects invalid categories, payments and amounts',()=>{
 const valid={date:'2026-10-06',category:'Operasional',description:'  Sabun laundry  ',amount:'250000',method:'Tunai'};
 const result=validateExpense(valid);assert.deepEqual(result.errors,{});assert.equal(result.values.amount,250000);assert.equal(result.values.description,'Sabun laundry');
 for(const amount of ['',0,-1,0.5,'NaN',Infinity,1000000001])assert.ok(validateExpense({...valid,amount}).errors.amount);
 assert.ok(validateExpense({...valid,date:'2026-02-30'}).errors.date);
 assert.ok(validateExpense({...valid,category:'Tidak ada'}).errors.category);
 assert.ok(validateExpense({...valid,method:'Kredit'}).errors.method);
 assert.ok(validateExpense({...valid,description:'  '}).errors.description);
});
