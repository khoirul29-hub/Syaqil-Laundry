import test from 'node:test';
import assert from 'node:assert/strict';
import {initialEmployees,initialServices,initialCorrections,validateEmployee,validateService,decideCorrection,effectiveCorrectionTotal} from '../src/managementData.js';

test('employee usernames are normalized and duplicate identities are rejected',()=>{
 const employee={...initialEmployees[1],username:' DESI ',phone:'+62 821 2345 6789'};
 const result=validateEmployee(employee,initialEmployees,employee.id);
 assert.equal(result.values.username,'desi');assert.equal(result.values.phone,'082123456789');assert.ok(result.errors.username);
 assert.deepEqual(validateEmployee(initialEmployees[1],initialEmployees,2).errors,{});
});
test('the last active Owner cannot be deactivated or demoted',()=>{
 assert.ok(validateEmployee({...initialEmployees[0],active:false},initialEmployees,1).errors.role);
 assert.ok(validateEmployee({...initialEmployees[0],role:'Kasir'},initialEmployees,1).errors.role);
 assert.deepEqual(validateEmployee({...initialEmployees[1],active:false},initialEmployees,2).errors,{});
});
test('service price and configurable duration have explicit valid bounds',()=>{
 for(const price of [0,-1,0.5,'invalid',10000001])assert.ok(validateService({...initialServices[0],price},initialServices,1).errors.price);
 for(const hours of [0,169,1.5])assert.ok(validateService({...initialServices[2],hours},initialServices,3).errors.hours);
 assert.equal(validateService({...initialServices[2],hours:'8'},initialServices,3).values.hours,8);
 assert.ok(validateService({...initialServices[0],name:' EKSPRES JAM '},initialServices,1).errors.name);
});
test('approval uses the proposed snapshot without mutating the original transaction',()=>{
 const pending=initialCorrections.find(r=>r.status==='Diproses'),snapshot=structuredClone(pending);
 const approved=decideCorrection(pending,'approve','Sesuai permintaan pelanggan');
 assert.equal(effectiveCorrectionTotal(approved),50000);assert.equal(pending.status,'Diproses');assert.deepEqual(pending,snapshot);
 assert.throws(()=>decideCorrection(approved,'approve','Disetujui ulang'));
 assert.throws(()=>decideCorrection(pending,'approve',''));
});
test('rejection preserves the original total; approved cancellations contribute zero',()=>{
 const pending=initialCorrections.find(r=>r.status==='Diproses');
 const rejected=decideCorrection(pending,'reject','Permintaan tidak sesuai');
 assert.equal(rejected.status,'Ditolak');assert.equal(effectiveCorrectionTotal(rejected),30000);
 for(const row of initialCorrections.filter(r=>r.type==='Pembatalan'))assert.equal(effectiveCorrectionTotal(row),0);
});
