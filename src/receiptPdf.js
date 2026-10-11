import {rupiah,quantity,dateText,methodName} from './lib.js';

export async function downloadReceipt(order,settings) {
 const {jsPDF}=await import('jspdf');
 const pdf=new jsPDF({unit:'mm',format:[80,220]});let y=9;
 const line=(text,{bold=false,size=8,center=false}={})=>{
  pdf.setFont('helvetica',bold?'bold':'normal');pdf.setFontSize(size);
  const lines=pdf.splitTextToSize(String(text),68);
  for(const part of lines){if(y>207){pdf.addPage([80,220]);y=9;}pdf.text(part,center?40:6,y,{align:center?'center':'left'});y+=4;}
 };
 const rule=()=>{y+=1;pdf.setDrawColor(160);pdf.line(6,y,74,y);y+=5;};
 const money=(label,value,bold=false)=>{if(y>207){pdf.addPage([80,220]);y=9;}pdf.setFont('helvetica',bold?'bold':'normal');pdf.setFontSize(8);pdf.text(label,6,y);pdf.text(rupiah(value),74,y,{align:'right'});y+=5;};
 line('SYAQIL LAUNDRY',{bold:true,size:13,center:true});line('Bersih • Cepat • Praktis',{center:true});
 if(settings?.address)line(settings.address,{center:true});if(settings?.phone)line(settings.phone,{center:true});
 if(settings?.demo)line('NOTA DEMO - BUKAN TRANSAKSI NYATA',{size:6,center:true});rule();
 line(order.number,{bold:true});line(dateText(order.created_at,true));line('Pelanggan: '+order.customer_name);if(order.cashier_name)line('Kasir: '+order.cashier_name);rule();
 for(const item of order.items){line(item.category+' / '+item.service_name,{bold:true});line(`${quantity(item.quantity)} ${item.unit} x ${rupiah(item.price)}`);money('Subtotal item',item.subtotal);}
 rule();money('Subtotal',order.subtotal);if(Number(order.discount)>0)money('Diskon'+(order.discount_percent!=null?` (${quantity(order.discount_percent)}%)`:''),-Number(order.discount));money('TOTAL',order.total,true);money(methodName[order.method],order.paid);money('Kembalian',order.change_amount);rule();
 if(order.notes){line('Catatan: '+order.notes);rule();}line('LUNAS',{bold:true,center:true});line('Terima kasih!',{center:true});line('Simpan nota untuk pengambilan cucian.',{size:7,center:true});
 pdf.save(order.number+'.pdf');
}
