import assert from 'node:assert/strict';
import {calculateDiscount} from '../src/lib.js';

assert.deepEqual(calculateDiscount(78000,'10'),{percent:10,amount:7800,error:''});
assert.deepEqual(calculateDiscount(25500,'12,5'),{percent:12.5,amount:3188,error:''});
assert.equal(calculateDiscount(78000,'').amount,0);
assert.equal(calculateDiscount(78000,'0').amount,0);
assert.equal(calculateDiscount(78000,'100').amount,78000);
assert.equal(calculateDiscount(120000,'10').amount,12000);
for(const value of ['-1','100.01','1.234','abc','1e2']) {
 const result=calculateDiscount(78000,value);
 assert.ok(result.error,`Reject invalid percentage ${value}`);
 assert.equal(result.amount,0);
}
console.log('PASS: percentage calculations, rounding, empty input, and invalid values');
