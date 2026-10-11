import test from 'node:test';
import assert from 'node:assert/strict';
import {initialSettings,validateSettings,normalizeSettingsPhone,makeSettingsBackup,parseSettingsBackup,settingsDate,settingsTime,previewMessage,MAX_BACKUP_BYTES} from '../src/settingsData.js';

test('business profile validates required fields and normalizes Indonesian phone numbers',()=>{
 const settings=structuredClone(initialSettings);
 settings.profile.name='  Laundry Uji  ';settings.profile.phone='+62 812-3456-7890';settings.profile.email='';
 const valid=validateSettings(settings);assert.deepEqual(valid.errors,{});assert.equal(valid.values.profile.name,'Laundry Uji');assert.equal(valid.values.profile.phone,'081234567890');
 assert.equal(normalizeSettingsPhone('(0751) 123456'),'0751123456');
 settings.profile.name='';settings.profile.phone='123';settings.profile.email='not-an-email';settings.profile.address='';settings.profile.owner='';
 assert.deepEqual(Object.keys(validateSettings(settings).errors).sort(),['name','owner','phone','email','address'].sort());
});

test('opening hours handle closed days and reject reversed, missing, malformed schedules',()=>{
 const settings=structuredClone(initialSettings);settings.hours[0].close='06:00';
 assert.ok(validateSettings(settings).errors['hours-0']);
 settings.hours[0].enabled=false;assert.equal(validateSettings(settings).errors['hours-0'],undefined);
 settings.hours[0].open='25:00';assert.ok(validateSettings(settings).errors['hours-0']);
 for(const hours of [null,{},'seven days',[],[null,null,null,null,null,null,null]]){
  const result=validateSettings({...settings,hours});assert.ok(Object.keys(result.errors).some(k=>k.startsWith('hours')));
 }
});

test('receipt preferences and message variables are validated and previewed',()=>{
 const settings=structuredClone(initialSettings);settings.profile.name='Laundry Uji';settings.receipt.prefix='TEST';
 assert.match(previewMessage(settings),/TEST-20261004-0001/);assert.match(previewMessage(settings),/Andi Pratama/);assert.match(previewMessage(settings),/Laundry Uji/);
 settings.notifications.template='Halo {password}, pesanan sudah selesai.';settings.receipt.prefix='../bad';settings.receipt.width='200';settings.receipt.footer='';settings.general.decimals='4';
 const {errors}=validateSettings(settings);for(const key of ['template','prefix','width','footer','decimals'])assert.ok(errors[key]);
});

test('settings backups round-trip, exclude unknown properties, and reject invalid files',()=>{
 const settings=structuredClone(initialSettings);settings.profile.name='Salinan Usaha';settings.hours[6].enabled=true;settings.general.sound=true;
 const backup=makeSettingsBackup(settings,'2026-10-09T08:00:00Z');
 assert.deepEqual(parseSettingsBackup(JSON.stringify(backup)),{settings,createdAt:'2026-10-09T08:00:00Z'});
 backup.settings.profile.extra='discard';backup.settings.general.extra=true;backup.settings.accounts=[{password:'not imported'}];
 assert.deepEqual(parseSettingsBackup(JSON.stringify(backup)).settings,settings);
 for(const raw of ['broken','null','{}',JSON.stringify({...backup,version:99}),JSON.stringify({...backup,settings:{...settings,hours:{}}}),JSON.stringify({...backup,settings:{...settings,hours:Array(7).fill(null)}})])assert.throws(()=>parseSettingsBackup(raw));
 assert.throws(()=>parseSettingsBackup(' '.repeat(MAX_BACKUP_BYTES+1)),/maksimal 3 MB/);
 assert.throws(()=>makeSettingsBackup({...settings,profile:{...settings.profile,name:''}}));
});

test('only bounded embedded PNG/JPEG logos and the bundled logo are accepted',()=>{
 for(const logo of [null,'/assets/logo.png','data:image/png;base64,aGVsbG8=','data:image/jpeg;base64,aGVsbG8='])assert.equal(validateSettings({...initialSettings,profile:{...initialSettings.profile,logo}}).errors.logo,undefined);
 for(const logo of ['https://example.org/tracking.png','javascript:alert(1)','data:image/svg+xml;base64,aGVsbG8=','data:image/png;base64,'+'A'.repeat(3000000)])assert.ok(validateSettings({...initialSettings,profile:{...initialSettings.profile,logo}}).errors.logo);
 // The UI additionally decodes images before accepting them; schema validation alone is not an image decoder.
});

test('receipt samples use selected date, time, and timezone formats',()=>{
 const settings=structuredClone(initialSettings);
 assert.equal(settingsDate(settings),'04 Okt 2026');assert.equal(settingsTime(settings),'10:30');
 settings.general.dateFormat='iso';settings.general.timezone='Asia/Jayapura';assert.equal(settingsDate(settings),'2026-10-04');assert.equal(settingsTime(settings),'12:30');
 settings.general.timeFormat='12';assert.equal(settingsTime(settings),'12:30 PM');
 settings.general.dateFormat='numeric';assert.equal(settingsDate(settings),'04/10/2026');
});
