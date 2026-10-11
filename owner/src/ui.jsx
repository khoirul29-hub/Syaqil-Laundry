import React,{useEffect,useId,useRef} from 'react';
import {createPortal} from 'react-dom';
import {CircleAlert,Inbox,LoaderCircle,X} from 'lucide-react';

export function Button({variant='primary',size='md',icon:Icon,loading=false,children,className='',disabled=false,...props}){
  return <button className={`ui-button ui-button--${variant} ui-button--${size} ${className}`} disabled={disabled||loading} {...props}>{loading?<LoaderCircle className="ui-spin" size={18} aria-hidden="true"/>:Icon?<Icon size={18} aria-hidden="true"/>:null}<span>{children}</span></button>;
}

export function Input({label,helper,error,icon:Icon,id:givenId,className='',...props}){
  const generatedId=useId(),id=givenId||generatedId;
  return <div className={`ui-field ${className}`}><label htmlFor={id}>{label}</label><div className={`ui-input-wrap ${error?'is-invalid':''}`}>{Icon&&<Icon size={18} aria-hidden="true"/>}<input id={id} aria-invalid={!!error} aria-describedby={error?`${id}-error`:helper?`${id}-help`:undefined} {...props} onInput={props.onInput||(props.type==='date'?props.onChange:undefined)}/></div>{error?<small className="ui-field-error" id={`${id}-error`} role="alert"><CircleAlert size={14}/>{error}</small>:helper?<small className="ui-field-help" id={`${id}-help`}>{helper}</small>:null}</div>;
}

export function Select({label,options=[],helper,error,id:givenId,className='',...props}){
  const generatedId=useId(),id=givenId||generatedId;
  return <div className={`ui-field ${className}`}><label htmlFor={id}>{label}</label><select id={id} aria-invalid={!!error} aria-describedby={error?`${id}-error`:helper?`${id}-help`:undefined} {...props}>{options.map(option=><option key={option.value} value={option.value}>{option.label}</option>)}</select>{error?<small className="ui-field-error" id={`${id}-error`} role="alert">{error}</small>:helper?<small className="ui-field-help" id={`${id}-help`}>{helper}</small>:null}</div>;
}

export function Textarea({label,helper,error,id:givenId,className='',...props}){
  const generatedId=useId(),id=givenId||generatedId;
  return <div className={`ui-field ${className}`}><label htmlFor={id}>{label}</label><textarea id={id} aria-invalid={!!error} aria-describedby={error?`${id}-error`:helper?`${id}-help`:undefined} {...props}/>{error?<small className="ui-field-error" id={`${id}-error`} role="alert">{error}</small>:helper?<small className="ui-field-help" id={`${id}-help`}>{helper}</small>:null}</div>;
}

export function Card({title,eyebrow,action,children,className='',...props}){
  return <section className={`ui-card ${className}`} {...props}>{(title||eyebrow||action)&&<header className="ui-card-head"><div>{eyebrow&&<span className="ui-eyebrow">{eyebrow}</span>}{title&&<h2>{title}</h2>}</div>{action}</header>}{children}</section>;
}

const badgeLabels={success:'Sukses',warning:'Perlu perhatian',info:'Informasi',danger:'Masalah',neutral:'Status'};
export function StatusBadge({tone='neutral',children}){
  return <span className={`ui-badge ui-badge--${tone}`} aria-label={`${badgeLabels[tone]||'Status'}: ${children}`}>{children}</span>;
}

export function EmptyState({icon:Icon=Inbox,title='Belum ada data',description,action,className=''}){
  return <div className={`ui-empty ${className}`}><span className="ui-empty-icon"><Icon size={28} strokeWidth={1.8} aria-hidden="true"/></span><strong>{title}</strong>{description&&<p>{description}</p>}{action&&<div className="ui-empty-action">{action}</div>}</div>;
}

export function LoadingState({label='Menyiapkan data...',rows=3}){
  return <div className="ui-loading" role="status" aria-live="polite"><div className="ui-loading-top"><LoaderCircle className="ui-spin" size={18}/><span>{label}</span></div>{Array.from({length:rows},(_,index)=><div className="ui-skeleton" key={index} style={{width:index===rows-1?'61%':'100%'}} aria-hidden="true"/>)}</div>;
}

export function DataTable({columns,rows=[],rowKey='id',emptyTitle='Data tidak ditemukan',className=''}){
  return <div className={`ui-table-wrap ${className}`}><table className="ui-table"><thead><tr>{columns.map(column=><th scope="col" key={column.key}>{column.label}</th>)}</tr></thead><tbody>{rows.length?rows.map((row,index)=><tr key={row[rowKey]??index}>{columns.map(column=><td key={column.key} data-label={column.label}>{column.render?column.render(row):row[column.key]}</td>)}</tr>):<tr><td colSpan={columns.length}><EmptyState title={emptyTitle} description="Coba kata kunci atau pilihan lain."/></td></tr>}</tbody></table></div>;
}

function Overlay({open,onClose,title,description,kind='modal',children,footer}){
  const titleId=useId(),descriptionId=useId(),panelRef=useRef(null);
  const closeRef=useRef(onClose);
  closeRef.current=onClose;
  useEffect(()=>{
    if(!open)return;
    const previous=document.activeElement;
    const priorOverflow=document.body.style.overflow;
    document.body.style.overflow='hidden';
    panelRef.current?.focus();
    const keydown=event=>{
      const dialogs=document.querySelectorAll('.ui-overlay-panel[aria-modal="true"]');
      if(dialogs[dialogs.length-1]!==panelRef.current)return;
      if(event.key==='Escape'){event.preventDefault();closeRef.current();}
      if(event.key!=='Tab')return;
      const targets=[...panelRef.current.querySelectorAll('button,[href],input,select,textarea,[tabindex]:not([tabindex="-1"])')].filter(element=>!element.disabled&&element.getClientRects().length);
      if(!targets.length){event.preventDefault();return;}
      const first=targets[0],last=targets.at(-1);
      if(event.shiftKey&&(document.activeElement===first||document.activeElement===panelRef.current)){event.preventDefault();last.focus();}
      else if(!event.shiftKey&&document.activeElement===last){event.preventDefault();first.focus();}
    };
    document.addEventListener('keydown',keydown);
    return()=>{document.removeEventListener('keydown',keydown);document.body.style.overflow=priorOverflow;if(previous?.isConnected)previous.focus();};
  },[open]);
  if(!open)return null;
  return createPortal(<div className={`ui-overlay ui-overlay--${kind}`}><button className="ui-overlay-backdrop" aria-label={`Tutup ${kind==='drawer'?'panel':'dialog'}`} onClick={onClose}/><div className="ui-overlay-panel" role="dialog" aria-modal="true" aria-labelledby={titleId} aria-describedby={description?descriptionId:undefined} ref={panelRef} tabIndex={-1}><header className="ui-overlay-head"><div><h2 id={titleId}>{title}</h2>{description&&<p id={descriptionId}>{description}</p>}</div><button className="ui-icon-button" aria-label="Tutup" onClick={onClose}><X size={20}/></button></header><div className="ui-overlay-body">{children}</div>{footer&&<footer className="ui-overlay-footer">{footer}</footer>}</div></div>,document.body);
}

export function Modal(props){return <Overlay kind="modal" {...props}/>;}
export function Drawer(props){return <Overlay kind="drawer" {...props}/>;}
