import assert from 'node:assert/strict';
import {jakartaDate,weeklyActivity,inPeriod,customerSummaries} from '../src/insights.js';

const now=new Date('2026-10-04T18:00:00Z');
assert.equal(jakartaDate(now),'2026-10-05');
const orders=[
 {id:1,customer_id:'1',created_at:'2026-09-29 23:59:00',status:'Diambil',total:'12000'},
 {id:2,customer_id:1,created_at:'2026-10-05 00:10:00',status:'Selesai',total:'18000'},
 {id:3,customer_id:2,created_at:'2026-10-06 00:00:00',status:'Diterima',total:'6000'},
 {id:4,customer_id:2,created_at:'2026-09-28 23:59:00',status:'Diambil',total:'6000'}
];
assert.deepEqual(orders.filter(o=>inPeriod(o,'week',now)).map(o=>o.id),[1,2]);
assert.deepEqual(orders.filter(o=>inPeriod(o,'today',now)).map(o=>o.id),[2]);
const activity=weeklyActivity(orders,now);
assert.equal(activity.length,7);
assert.equal(activity[0].date,'2026-09-29');
assert.deepEqual(activity.map(d=>d.count),[1,0,0,0,0,0,1]);
assert.equal(weeklyActivity([],now).every(d=>d.count===0),true);
const customers=customerSummaries([{id:1,name:'Andi'},{id:2,name:'Nadia'},{id:3,name:'Rian'}],orders);
assert.equal(customers[0].visits,2);
assert.equal(customers[0].active,1);
assert.equal(customers[0].lastOrder.id,2);
assert.equal(customers[2].visits,0);
assert.equal(customers[2].lastOrder,null);
console.log('PASS: Jakarta day boundary, seven-day window, future exclusion, empty activity, and customer visits');
