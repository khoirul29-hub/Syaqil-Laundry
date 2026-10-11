import assert from 'node:assert/strict';
import {aggregateReport,previousRange,growth} from '../src/reportData.js';
const data={services:[{id:1,name:'Reguler'}],customers:[{id:1,created_at:'2026-10-04 10:00:00'},{id:2,created_at:'2026-10-03 23:59:59'}],orders:[
 {created_at:'2026-10-04 00:00:00',total:39375,subtotal:45000,method:'cash',status:'Selesai',items:[{service_id:1,service_name:'Reguler',unit:'kg',quantity:2},{service_id:1,service_name:'Reguler',unit:'kg',quantity:2.5},{service_id:2,service_name:'Ekspres',unit:'pcs',quantity:3}]},
 {created_at:'2026-10-05 23:59:59',total:20000,method:'unknown',status:'Diterima',items:[{service_id:2,service_name:'Ekspres',unit:'kg',quantity:1}]},
 {created_at:'2026-10-06 00:00:00',total:99000,method:'cash',status:'Diambil',items:[]},
]};
const report=aggregateReport(data,'2026-10-04','2026-10-05');
assert.equal(report.count,2);assert.equal(report.revenue,59375);assert.equal(report.weight,5.5);assert.equal(report.customers,1);
assert.deepEqual(report.services,[{name:'Ekspres',count:2},{name:'Reguler',count:1}]);
assert.equal(new Map(report.methods).get('other'),1);assert.equal(new Map(report.statuses).get('Selesai'),1);
assert.equal(report.daily.reduce((sum,d)=>sum+d.revenue,0),report.revenue);
assert.equal(report.daily.reduce((sum,d)=>sum+d.weight,0),report.weight);
assert.equal(report.daily.reduce((sum,d)=>sum+d.customers,0),report.customers);
assert.deepEqual(previousRange('2026-10-04','2026-10-05','previous'),{from:'2026-10-02',to:'2026-10-03'});
assert.deepEqual(previousRange('2024-02-29','2024-03-01','year'),{from:'2023-02-28',to:'2023-03-01'});
assert.equal(previousRange('2026-10-04','2026-10-05','none'),null);
assert.equal(growth(12,10),'+20%');assert.equal(growth(5,10),'-50%');assert.equal(growth(5,0),'Baru');assert.equal(growth(0,0),'0%');
const empty=aggregateReport(data,'2026-11-01','2026-11-07');assert.equal(empty.count,0);assert.equal(empty.daily.length,7);assert.equal(empty.daily.every(d=>d.revenue===0),true);
console.log('PASS: inclusive dates, discounted revenue, kg-only weights, per-note service ranking, daily totals, unknown methods, previous period, leap year, and zero-baseline growth');
