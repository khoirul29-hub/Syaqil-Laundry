import React,{useEffect,useState} from 'react';
import {Check,Clock3,KeyRound,Pencil,RefreshCw} from 'lucide-react';
import {dateLabel} from './mockData';

export function useDetailDrawer(){
 const [small,setSmall]=useState(()=>innerWidth<1100),[open,setOpen]=useState(false);
 useEffect(()=>{const resize=()=>{setSmall(innerWidth<1100);if(innerWidth>=1100)setOpen(false);};addEventListener('resize',resize);return()=>removeEventListener('resize',resize);},[]);
 return {small,open,setOpen};
}
export function ManagementHeading({icon:Icon,title,description,action}){return <header className="mg-heading"><Icon size={26}/><div><h2>{title}</h2>{description&&<p>{description}</p>}</div>{action}</header>;}
export function ActiveBadge({active}){return <span className={`mg-status ${active?'is-active':'is-inactive'}`}><i/>{active?'Aktif':'Nonaktif'}</span>;}
export function Toggle({checked,onChange,label='Status aktif',disabled=false}){return <button className={`mg-toggle ${checked?'is-on':''}`} type="button" role="switch" aria-checked={checked} aria-label={label} disabled={disabled} onClick={()=>onChange(!checked)}><i>{checked&&<Check size={12}/>}</i></button>;}
export function RoleBadge({role}){return <span className={`mg-role ${role==='Owner'?'is-owner':''}`}>{role}</span>;}
export function ActivityTable({rows}){return <div className="ow-table-scroll"><table className="ow-table mg-activity-table"><thead><tr>{['Tanggal','Karyawan','Aktivitas','Detail'].map(h=><th key={h}>{h}</th>)}</tr></thead><tbody>{rows.map(row=>{const Icon=row.type==='Reset Password'?KeyRound:row.type==='Update Data'?Pencil:row.type==='Login'?RefreshCw:Clock3;return <tr key={row.id}><td>{dateLabel(row.date)} {row.date.slice(11,16)}</td><td>{row.name}</td><td><span className="mg-activity-kind"><Icon size={15}/>{row.type}</span></td><td>{row.detail}</td></tr>;})}</tbody></table></div>;}
