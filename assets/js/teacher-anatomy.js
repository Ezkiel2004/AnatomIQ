'use strict';
(() => {
    const preview=new URLSearchParams(location.search).get('preview')==='student';
    const previewLink=document.getElementById('previewToggle');
    const editLink=document.getElementById('editAnatomy');
    const related=document.getElementById('relatedModules');
    const presentation=document.getElementById('presentationToggle');
    let requestVersion=0;
    previewLink.textContent=preview?'Return to teacher view':'Student preview';
    previewLink.href=preview?'anatomy.html':'anatomy.html?preview=student';
    document.getElementById('previewStatus').textContent=preview
        ?'Student preview: only student-visible systems are shown. Your teacher account remains signed in.'
        :'Teacher view includes systems hidden from students.';
    document.getElementById('relatedContent').hidden=preview;
    editLink.hidden=preview;

    function setPresentation(enabled){
        document.body.classList.toggle('anatomy-presenting',enabled);
        presentation.setAttribute('aria-pressed',String(enabled));
        presentation.textContent=enabled?'Exit presentation (Esc)':'Presentation mode';
        if(enabled){document.getElementById('sidebar').classList.remove('mobile-open');document.getElementById('sidebarOverlay').classList.remove('visible');window.scrollTo(0,0);}
    }
    presentation.onclick=()=>setPresentation(!document.body.classList.contains('anatomy-presenting'));
    document.addEventListener('keydown',event=>{
        if(event.key==='Escape' && document.body.classList.contains('anatomy-presenting')){setPresentation(false);presentation.focus();}
    });
    document.addEventListener('anatomysystemselected',async event=>{
        const system=event.detail;
        const version=++requestVersion;
        editLink.href='content.html?system='+encodeURIComponent(system.id);
        previewLink.href='anatomy.html?'+new URLSearchParams({...(!preview?{preview:'student'}:{}),system:system.id});
        const url=new URL(location.href);url.searchParams.set('system',system.id);history.replaceState(null,'',url);
        if(preview)return;
        related.textContent='Loading related modules...';
        try{
            const response=await fetch(API_BASE+'/modules.php?system='+encodeURIComponent(system.id),{credentials:'same-origin'});
            const result=await response.json();
            if(!response.ok || !result.success)throw new Error('Unable to load related lessons.');
            if(version!==requestVersion)return;
            related.replaceChildren();
            if(!result.data.length)related.textContent='No modules or lessons are linked to this system yet. ';
            result.data.forEach(module=>{
                const link=document.createElement('a');link.className='btn btn-secondary';
                link.href='modules.html?module_id='+encodeURIComponent(module.module_id);
                link.textContent=module.title+' ('+module.status+')';related.append(link);
            });
            const manage=document.createElement('a');manage.href='modules.html';manage.className='btn btn-secondary';manage.textContent='Manage modules and lessons';related.append(manage);
        }catch(error){if(version===requestVersion)related.textContent='Related lessons could not be loaded. Select the system again to retry.';}
    });
})();
