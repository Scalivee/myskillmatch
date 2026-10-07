import Alpine from '@alpinejs/csp';
Alpine.data('navigation',()=>({open:false,toggle(){this.open=!this.open;},close(){this.open=false;}}));
Alpine.start();
document.querySelectorAll('.table-wrap').forEach(region=>{region.tabIndex=0;region.setAttribute('role','region');region.setAttribute('aria-label','Tabel data; geser mendatar untuk melihat seluruh kolom');});
document.querySelectorAll('form[data-confirm]').forEach(form=>form.addEventListener('submit',e=>{if(!confirm(form.dataset.confirm))e.preventDefault();}));
const summary=document.querySelector('[data-error-summary]');if(summary)summary.focus();
// Give summary links a working target even in compact repeated administration forms.
summary?.querySelectorAll('a[data-field]').forEach(link=>{
    const field=link.dataset.field;
    let target=document.getElementById(field.replaceAll('.','_'));
    if(!target)target=[...document.querySelectorAll('input,select,textarea')].find(el=>el.name===field);
    if(target){if(!target.id)target.id='error-field-'+field.replaceAll('.','_');link.href='#'+target.id;target.setAttribute('aria-invalid','true');link.addEventListener('click',()=>{for(let parent=target.parentElement;parent;parent=parent.parentElement){if(parent.tagName==='DETAILS')parent.open=true;}target.focus();});}
});
const timer=document.querySelector('[data-expires]');
if(timer){const expires=Date.parse(timer.dataset.expires);const tick=()=>{const s=Math.max(0,Math.floor((expires-Date.now())/1000));timer.textContent=Math.floor(s/60)+':'+String(s%60).padStart(2,'0');if(s===0){clearInterval(id);document.querySelector('[data-assessment-form]')?.requestSubmit(document.querySelector('[name="finish"]'));}};const id=setInterval(tick,1000);tick();}

document.querySelector('#add-question')?.addEventListener('click',()=>{const parent=document.querySelector('#questions');const n=parent.children.length;if(n>=10)return;const clone=parent.firstElementChild.cloneNode(true);clone.querySelectorAll('input,select').forEach(el=>{el.name=el.name.replace(/questions\[0\]/,'questions['+n+']');if(el.id)el.id=el.id.replace('questions_0','questions_'+n);if(el.tagName==='INPUT')el.value='';});clone.querySelectorAll('label[for]').forEach(el=>el.htmlFor=el.htmlFor.replace('questions_0','questions_'+n));clone.querySelector('legend').textContent='Soal '+(n+1);parent.append(clone);});

document.querySelector('[data-final-assessment]')?.addEventListener('click',event=>{if(!confirm('Kirim jawaban dan akhiri assessment? Jawaban tidak dapat diubah.'))event.preventDefault();});

document.querySelectorAll('form[method="post"]').forEach(form=>form.addEventListener('submit',event=>{
    if(event.defaultPrevented)return;
    form.setAttribute('aria-busy','true');
    let status=form.querySelector('[data-submit-status]');
    if(!status){status=document.createElement('p');status.dataset.submitStatus='';status.className='hint';status.setAttribute('role','status');form.append(status);}
    status.textContent='Sedang memproses. Mohon tunggu…';
}));
window.addEventListener('pageshow',()=>{document.querySelectorAll('form[aria-busy]').forEach(form=>form.removeAttribute('aria-busy'));document.querySelectorAll('[data-submit-status]').forEach(el=>el.remove());});
