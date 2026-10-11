export const initials=name=>String(name||'').trim().split(/\s+/).map(n=>n[0]).slice(0,2).join('').toUpperCase();
const xml=value=>String(value??'').replace(/[<>&"']/g,c=>({'<':'&lt;','>':'&gt;','&':'&amp;','"':'&quot;',"'":'&apos;'}[c])).replace(/[\u0000-\u0008\u000b\u000c\u000e-\u001f]/g,'');
const encoder=new TextEncoder();
const crc32=bytes=>{let crc=0xffffffff;for(const byte of bytes){crc^=byte;for(let i=0;i<8;i++)crc=(crc>>>1)^((crc&1)?0xedb88320:0);}return (crc^0xffffffff)>>>0;};
function zipFiles(files) {
 const chunks=[],central=[];let offset=0,size=0;
 for(const [filename,content] of files){
  const name=encoder.encode(filename),body=encoder.encode(content),crc=crc32(body);
  const local=new Uint8Array(30+name.length),view=new DataView(local.buffer);
  view.setUint32(0,0x04034b50,true);view.setUint16(4,20,true);view.setUint32(14,crc,true);view.setUint32(18,body.length,true);view.setUint32(22,body.length,true);view.setUint16(26,name.length,true);local.set(name,30);
  const entry=new Uint8Array(46+name.length),cv=new DataView(entry.buffer);
  cv.setUint32(0,0x02014b50,true);cv.setUint16(4,20,true);cv.setUint16(6,20,true);cv.setUint32(16,crc,true);cv.setUint32(20,body.length,true);cv.setUint32(24,body.length,true);cv.setUint16(28,name.length,true);cv.setUint32(42,offset,true);entry.set(name,46);
  chunks.push(local,body);central.push(entry);offset+=local.length+body.length;size+=entry.length;
 }
 const end=new Uint8Array(22),ev=new DataView(end.buffer);ev.setUint32(0,0x06054b50,true);ev.setUint16(8,files.length,true);ev.setUint16(10,files.length,true);ev.setUint32(12,size,true);ev.setUint32(16,offset,true);
 const output=new Uint8Array(offset+size+22);let cursor=0;for(const bytes of [...chunks,...central,end]){output.set(bytes,cursor);cursor+=bytes.length;}return output;
}
export function workbookBytes(columns,rows) {
 const sheet=[columns,...rows].map((row,index)=>`<row r="${index+1}">${row.map(value=>typeof value==='number'&&Number.isFinite(value)?`<c><v>${value}</v></c>`:`<c t="inlineStr"><is><t xml:space="preserve">${xml(value)}</t></is></c>`).join('')}</row>`).join('');
 return zipFiles([
  ['[Content_Types].xml','<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>'],
  ['_rels/.rels','<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>'],
  ['xl/workbook.xml','<?xml version="1.0" encoding="UTF-8"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Syaqil Laundry" sheetId="1" r:id="rId1"/></sheets></workbook>'],
  ['xl/_rels/workbook.xml.rels','<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>'],
  ['xl/worksheets/sheet1.xml',`<?xml version="1.0" encoding="UTF-8"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>${sheet}</sheetData></worksheet>`]
 ]);
}
export function exportExcel(filename,columns,rows) {
 const url=URL.createObjectURL(new Blob([workbookBytes(columns,rows)],{type:'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'}));
 const link=document.createElement('a');link.href=url;link.download=filename.replace(/\.(csv|xlsx)$/i,'')+'.xlsx';link.click();setTimeout(()=>URL.revokeObjectURL(url),1000);
}
export const orderWeight=order=>order.items.reduce((sum,item)=>sum+Number(item.quantity),0);
export function dateMatches(order,from,to){const day=order.created_at.slice(0,10);return (!from||day>=from)&&(!to||day<=to);}
