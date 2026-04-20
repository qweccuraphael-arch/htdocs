'use strict';
document.querySelectorAll('.song-row,.featured-card').forEach((el,i)=>{
  el.style.cssText='opacity:0;transform:translateY(12px);transition:opacity .3s ease,transform .3s ease';
  setTimeout(()=>{el.style.opacity='1';el.style.transform='translateY(0)'},40+i*30);
});
document.querySelectorAll('.flash-msg').forEach(el=>{
  setTimeout(()=>{el.style.cssText='opacity:0;transition:opacity .4s';setTimeout(()=>el.remove(),400)},4000);
});
document.querySelectorAll('[data-confirm]').forEach(el=>{
  el.addEventListener('click',e=>{if(!confirm(el.dataset.confirm||'Are you sure?'))e.preventDefault()});
});
