'use strict';
const teacherExplorer=document.body.dataset.anatomyRole==='teacher';
const studentPreview=teacherExplorer && new URLSearchParams(location.search).get('preview')==='student';
let selectedSystem=null, activeSeconds=0, lastTick=Date.now(), interactions=0, viewed=new Set(), viewerReady=false, selectionVersion=0;
const modelMessage=document.getElementById('modelMessage');
function showStructure(structure){
    document.getElementById('structureTitle').textContent=structure.name;
    document.getElementById('structureDescription').textContent=structure.desc || 'No description provided.';
    viewed.add(structure.name);interactions++;
    if(viewerReady)AnatomyViewer.highlight(structure);
}
async function flushExploration(){
    if(teacherExplorer || !selectedSystem || activeSeconds<1)return;
    const payload={system_id:selectedSystem.system_id,duration_secs:Math.floor(activeSeconds),interactions,structures_viewed:[...viewed]};
    activeSeconds=0;interactions=0;viewed.clear();
    try{const response=await fetch(API_BASE+'/analytics/exploration.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload),credentials:'same-origin',keepalive:true});if(!response.ok)console.warn('Exploration progress could not be saved.');}catch(error){console.warn('Exploration progress could not be saved.');}
}
async function selectSystem(id){
    const version=++selectionVersion;
    await flushExploration(); if(version!==selectionVersion)return;
    selectedSystem=AnatomyData.getSystem(id);if(!selectedSystem)return;
    const system=selectedSystem;
    document.dispatchEvent(new CustomEvent('anatomysystemselected',{detail:system}));
    document.getElementById('systemTitle').textContent=system.name;
    document.getElementById('systemDescription').textContent=system.description || 'Your teacher has not added a description yet.';
    document.getElementById('systemSources').textContent=system.source;
    document.getElementById('structureTitle').textContent='Choose a structure';document.getElementById('structureDescription').textContent='Select a structure below or a labeled part of the model.';
    const facts=document.getElementById('systemFacts');facts.replaceChildren();
    Object.entries(system.keyFacts).forEach(([key,value])=>{const item=document.createElement('p');const strong=document.createElement('strong');strong.textContent=key+': ';item.append(strong,document.createTextNode(value));facts.append(item);});
    const structures=document.getElementById('structures');structures.replaceChildren();
    system.structures.forEach(s=>{const button=document.createElement('button');button.className='structure-button';button.textContent=s.name;button.onclick=()=>showStructure(s);structures.append(button);});
    if(!system.structures.length)structures.textContent='No structures added yet.';
    modelMessage.style.display='grid';modelMessage.textContent=system.modelUrl?'Loading model…':'No 3D model has been added for this system. You can read the available content alongside the viewer.';
    document.getElementById('practicePrompt').textContent='';document.getElementById('practiceResult').textContent='';document.getElementById('practiceChoices').replaceChildren();
    document.getElementById('practiceButton').disabled=system.structures.filter(s=>s.desc).length<2;
    if(!viewerReady)return;
    try{const loaded=await AnatomyViewer.load(system,pct=>{if(version===selectionVersion)modelMessage.textContent=pct===null?'Loading model…':`Loading model: ${pct}%`;});if(version===selectionVersion && loaded)modelMessage.style.display='none';}
    catch(error){if(version===selectionVersion)modelMessage.textContent='The model could not be loaded. Try selecting the system again or contact your teacher.';}
}
document.getElementById('systemSelect').onchange=e=>selectSystem(e.target.value);
document.getElementById('practiceButton').onclick=()=>{
    const entries=selectedSystem?.structures.filter(s=>s.desc)||[];if(entries.length<2)return;
    const target=entries[Math.floor(Math.random()*entries.length)];
    document.getElementById('practicePrompt').textContent=target.desc;
    document.getElementById('practiceResult').textContent='';const list=document.getElementById('practiceChoices');list.replaceChildren();
    const shuffled=[...entries];for(let i=shuffled.length-1;i>0;i--){const j=Math.floor(Math.random()*(i+1));[shuffled[i],shuffled[j]]=[shuffled[j],shuffled[i]];}
    const choices=[target,...shuffled.filter(s=>s!==target).slice(0,3)].sort(()=>Math.random()-.5);
    choices.forEach(s=>{const button=document.createElement('button');button.className='btn btn-secondary';button.textContent=s.name;button.onclick=()=>{document.getElementById('practiceResult').textContent=s===target?'Correct. '+target.desc:'Try again. Read the description carefully.';};list.append(button);});
};
document.getElementById('resetModel').onclick=()=>viewerReady&&AnatomyViewer.resetView();
document.getElementById('zoomIn').onclick=()=>viewerReady&&AnatomyViewer.zoom(.85);
document.getElementById('zoomOut').onclick=()=>viewerReady&&AnatomyViewer.zoom(1.15);
document.getElementById('rotateModel').onclick=e=>{if(viewerReady){AnatomyViewer.controls.autoRotate=!AnatomyViewer.controls.autoRotate;e.target.setAttribute('aria-pressed',String(AnatomyViewer.controls.autoRotate));}};
document.getElementById('exportModel').onclick=()=>viewerReady&&AnatomyViewer.root&&AnatomyViewer.screenshot();
if(!teacherExplorer){
setInterval(()=>{const now=Date.now();if(!document.hidden && selectedSystem)activeSeconds+=Math.min((now-lastTick)/1000,2);lastTick=now;},1000);
setInterval(flushExploration,45000);window.addEventListener('pagehide',flushExploration);
document.addEventListener('visibilitychange',()=>{lastTick=Date.now();if(document.hidden)flushExploration();});
}
(async()=>{
    if(!await Auth.requireAuth(teacherExplorer?'teacher':'student'))return;
    try{
        await AnatomyData.load();
        if(studentPreview)AnatomyData.systems=AnatomyData.systems.filter(s=>s.isActive);
        const systems=AnatomyData.systems;const select=document.getElementById('systemSelect');select.replaceChildren();
        systems.forEach(s=>select.add(new Option(s.name+(teacherExplorer&&!studentPreview&&!s.isActive?' (hidden)':''),s.id)));
        if(!systems.length){select.disabled=true;modelMessage.textContent='No anatomy content has been published yet.';document.getElementById('systemTitle').textContent='Anatomy content is coming soon';return;}
        try{AnatomyViewer.init(document.getElementById('anatomyCanvas'));AnatomyViewer.onSelect=showStructure;viewerReady=true;}catch(error){modelMessage.textContent='3D viewing is unavailable on this device. You can still read the anatomy content.';}
        const params=new URLSearchParams(location.search);
        const lessonId=studentPreview?null:params.get('lesson_id');
        if(lessonId){const response=await fetch(API_BASE+'/lessons.php?id='+encodeURIComponent(lessonId));const result=await response.json();if(result.success && /\.glb$/i.test(result.data.media_url||'')){const system=AnatomyData.getSystem(result.data.system_code);if(system){system.modelUrl=result.data.media_url;system.name=result.data.title;}}}
        const requested=params.get('system');const id=AnatomyData.getSystem(requested)?.id||systems[0].id;select.value=id;await selectSystem(id);
        if(!viewerReady)modelMessage.textContent='3D viewing is unavailable on this device. You can still read the anatomy content.';
    }catch(error){modelMessage.textContent='Unable to load anatomy content. Reload the page to try again.';}
})();
