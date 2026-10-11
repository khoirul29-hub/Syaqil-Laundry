import assert from 'node:assert/strict';
import {mkdtempSync,writeFileSync,readFileSync} from 'node:fs';
import {tmpdir} from 'node:os';
import {join} from 'node:path';
import {spawnSync} from 'node:child_process';
import {workbookBytes,dateMatches,orderWeight} from '../src/referenceUtils.js';
import {downloadReceipt} from '../src/receiptPdf.js';

const dir=mkdtempSync(join(tmpdir(),'syaqil-export-'));
const path=join(dir,'data.xlsx');
writeFileSync(path,workbookBytes(['Nama','Diskon (%)','Total','Catatan'],[['A & B <Laundry>',12.5,39375,'=1+1'],['Baris\nbaru',0,0,'Bersih\u0001']]));
const validation=spawnSync('python3',['-c',`
import sys,zipfile,xml.etree.ElementTree as ET
with zipfile.ZipFile(sys.argv[1]) as z:
 assert z.testzip() is None
 for name in z.namelist(): ET.fromstring(z.read(name))
 ns={'s':'http://schemas.openxmlformats.org/spreadsheetml/2006/main'}
 root=ET.fromstring(z.read('xl/worksheets/sheet1.xml'))
 rows=root.findall('s:sheetData/s:row',ns)
 assert len(rows)==3
 values=rows[1].findall('s:c',ns)
 assert values[0].find('s:is/s:t',ns).text=='A & B <Laundry>'
 assert values[1].find('s:v',ns).text=='12.5'
 assert values[2].find('s:v',ns).text=='39375'
 assert values[3].attrib['t']=='inlineStr'
 assert values[3].find('s:is/s:t',ns).text=='=1+1'
 assert not root.findall('.//s:f',ns)
 assert rows[2].findall('s:c',ns)[3].find('s:is/s:t',ns).text=='Bersih'
print('PASS: Excel ZIP integrity, XML, numeric values and literal formula-like text')
`,path],{encoding:'utf8'});
assert.equal(validation.status,0,validation.stderr);process.stdout.write(validation.stdout);
assert.equal(dateMatches({created_at:'2026-10-05 00:00:00'},'2026-10-05','2026-10-05'),true);
assert.equal(dateMatches({created_at:'2026-10-06 00:00:00'},'2026-10-05','2026-10-05'),false);
assert.equal(orderWeight({items:[{quantity:'1.25'},{quantity:'3.50'}]}),4.75);
process.chdir(dir);
await downloadReceipt({number:'SYQ-TEST-001',created_at:'2026-10-05 10:24:00',customer_name:'Pelanggan Uji',cashier_name:'Kasir',items:[{category:'Kiloan',service_name:'Ekspres 1 Hari',quantity:4.5,unit:'kg',price:10000,subtotal:45000}],subtotal:45000,discount_percent:12.5,discount:5625,total:39375,method:'cash',paid:50000,change_amount:10625,notes:'Pisahkan pakaian putih.'},{demo:true});
const pdf=readFileSync('SYQ-TEST-001.pdf','latin1');
assert.ok(pdf.startsWith('%PDF-'));
assert.ok(pdf.includes('39.375'));
assert.ok(pdf.includes('12,5%'));
assert.ok(pdf.includes('10.625'));
assert.ok(pdf.includes('NOTA DEMO'));
console.log('PASS: receipt PDF generation, percentage discount, total and cash change');
console.log('PASS: inclusive date range and decimal laundry weight');
